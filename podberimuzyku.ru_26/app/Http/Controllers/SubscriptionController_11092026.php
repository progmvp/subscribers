<?php

namespace App\Http\Controllers;

use App\Models\SubscrPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    /**
     * Публичная форма получения подписки.
     *
     * Тариф test намеренно не выводится.
     */
    public function show()
    {
        $plans = DB::table('plans')
            ->where('is_active', 1)
            ->whereIn('code', ['start', 'base', 'full'])
            ->orderBy('id')
            ->get();

        return view('subscription.subscribe', [
            'plans' => $plans,
        ]);
    }

    /**
     * Создание пользователя и платежа для первой покупки.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],

            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(function ($query) {
                    $query
                        ->where('is_active', 1)
                        ->whereIn('code', ['start', 'base', 'full']);
                }),
            ],
        ]);

        $name = trim($validated['name']);
        $email = mb_strtolower(trim($validated['email']));
        $planId = (int) $validated['plan_id'];

        $plan = DB::table('plans')
            ->where('id', $planId)
            ->where('is_active', 1)
            ->whereIn('code', ['start', 'base', 'full'])
            ->first();

        if (!$plan) {
            return back()
                ->withInput()
                ->withErrors([
                    'plan_id' => 'Выбранный тариф недоступен.',
                ]);
        }

        /*
         * Пользователь и платёж создаются атомарно.
         */
        $result = DB::transaction(function () use (
            $name,
            $email,
            $plan
        ) {
            $user = User::query()
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            /*
             * Если пользователь уже существует и подписка активна,
             * новая первая покупка через эту форму не создаётся.
             * Продление выполняется из ЛК.
             */
            if (
                $user
                && $user->subscription_expires_at !== null
                && $user->subscription_expires_at->greaterThan(now())
            ) {
                return [
                    'type' => 'active_subscription',
                    'user' => $user,
                ];
            }

            /*
             * Если пользователь существует, но подписка истекла,
             * тариф start разрешён только через 24 часа после
             * точного времени окончания.
             *
             * base/full после окончания доступны сразу.
             */
            if (
                $user
                && $user->subscription_expires_at !== null
                && $user->subscription_expires_at->isPast()
                && $plan->code === 'start'
            ) {
                $startAvailableAt = $user
                    ->subscription_expires_at
                    ->copy()
                    ->addHours(24);

                if ($startAvailableAt->greaterThan(now())) {
                    return [
                        'type' => 'start_unavailable',
                        'available_at' => $startAvailableAt,
                    ];
                }
            }

            /*
             * Новый пользователь.
             *
             * Пароль пользователю сейчас не нужен:
             * вход в ЛК выполняется через код из e-mail.
             *
             * Случайный пароль сохраняется только потому,
             * что поле users.password существует.
             */
            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Str::random(64),
                ]);
            }

            /*
             * Если пользователь уже существует, его имя не перезаписываем.
             */

            /*
             * Если для этого пользователя и этого тарифа уже существует
             * ожидающий платёж, используем его повторно.
             *
             * Это защищает от создания нескольких одинаковых pending-платежей
             * при повторном нажатии кнопки.
             */
            $existingPayment = SubscrPayment::query()
                ->where('user_id', $user->id)
                ->where('plan_id', $plan->id)
                ->where('status', 'pending')
                ->latest('id')
                ->first();

            if ($existingPayment) {
                return [
                    'type' => 'payment',
                    'user' => $user,
                    'payment' => $existingPayment,
                    'plan' => $plan,
                ];
            }

            $payment = SubscrPayment::create([
                'user_id' => $user->id,
                'subscription_id' => null,
                'plan_id' => $plan->id,
                'payment_id' => (string) Str::uuid(),
                'amount' => $plan->price,
                'status' => 'pending',
                'paid_at' => null,
            ]);

            return [
                'type' => 'payment',
                'user' => $user,
                'payment' => $payment,
                'plan' => $plan,
            ];
        });

        if ($result['type'] === 'active_subscription') {
            return back()
                ->withInput()
                ->withErrors([
                    'email' =>
                        'Указанный e-mail уже имеет активную подписку. '
                        . 'Для продления используйте личный кабинет.',
                ]);
        }

        if ($result['type'] === 'start_unavailable') {
            return back()
                ->withInput()
                ->withErrors([
                    'plan_id' =>
                        'Тариф «Стартовый» будет доступен после '
                        . $result['available_at']->format('d.m.Y H:i:s')
                        . '.',
                ]);
        }

        $yoomoneyReceiver = env('YOOMONEY_RECEIVER');

        if (!$yoomoneyReceiver) {
            abort(500, 'YOOMONEY_RECEIVER is not configured');
        }

        return response()->view('payment.yoomoney', [
            'name' => $result['user']->name,
            'email' => $result['user']->email,
            'plan' => $result['plan']->code,
            'planName' => $result['plan']->name,
            'amount' => $result['plan']->price,
            'paymentId' => $result['payment']->payment_id,
            'yoomoneyReceiver' => $yoomoneyReceiver,
        ]);
    }
}
