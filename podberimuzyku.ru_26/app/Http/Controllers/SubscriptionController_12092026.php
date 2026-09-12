<?php

namespace App\Http\Controllers;

use App\Models\SubscrCheckout;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    /**
     * Время жизни checkout.
     *
     * Checkout действителен 20 минут.
     */
    private const CHECKOUT_TTL_MINUTES = 20;

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
     * Создание временного checkout для первой покупки.
     *
     * ВАЖНО:
     * - новый пользователь здесь НЕ создаётся;
     * - запись в subscr_payments здесь НЕ создаётся;
     * - до успешной оплаты используется только subscr_checkout.
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
         * Все действия с checkout выполняем в одной транзакции.
         */
        $result = DB::transaction(function () use (
            $name,
            $email,
            $plan
        ) {
            $now = now();

            /*
             * Удаляем просроченные checkout.
             *
             * Это поддерживает правило:
             * просроченный checkout автоматически становится недействительным
             * и удаляется из таблицы.
             */
            SubscrCheckout::query()
                ->where('expires_at', '<=', $now)
                ->delete();

            /*
             * Проверяем существующего пользователя.
             *
             * Если пользователя ещё нет — это нормально.
             * На этом этапе создавать его нельзя.
             */
            $user = User::query()
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            /*
             * Если пользователь уже существует и подписка активна,
             * новая первая покупка через публичную форму не создаётся.
             *
             * Продление выполняется из ЛК.
             */
            if (
                $user
                && $user->subscription_expires_at !== null
                && $user->subscription_expires_at->greaterThan($now)
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

                if ($startAvailableAt->greaterThan($now)) {
                    return [
                        'type' => 'start_unavailable',
                        'available_at' => $startAvailableAt,
                    ];
                }
            }

            /*
             * Проверяем существующий действующий checkout
             * для этого e-mail.
             *
             * Если тариф тот же — повторно используем checkout
             * и тот же payment_id.
             */
            $existingCheckout = SubscrCheckout::query()
                ->where('email', $email)
                ->where('status', 'pending')
                ->where('expires_at', '>', $now)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existingCheckout) {
                if ((int) $existingCheckout->plan_id === (int) $plan->id) {
                    return [
                        'type' => 'checkout',
                        'checkout' => $existingCheckout,
                        'plan' => $plan,
                    ];
                }

                /*
                 * Тот же e-mail, но другой тариф.
                 *
                 * Старый checkout больше не должен использоваться.
                 * Удаляем его и создаём новый payment_id.
                 */
                SubscrCheckout::query()
                    ->where('email', $email)
                    ->where('status', 'pending')
                    ->delete();
            }

            /*
             * Новый checkout.
             *
             * Здесь НЕ создаются:
             * - users
             * - subscr_payments
             *
             * Они появятся только после подтверждённой оплаты.
             */
            $checkout = SubscrCheckout::create([
                'name' => $name,
                'email' => $email,
                'plan_id' => $plan->id,
                'payment_id' => (string) Str::uuid(),
                'amount' => $plan->price,
                'status' => 'pending',
                'expires_at' => $now->copy()->addMinutes(self::CHECKOUT_TTL_MINUTES),
            ]);

            return [
                'type' => 'checkout',
                'checkout' => $checkout,
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

        $checkout = $result['checkout'];
        $plan = $result['plan'];

        return response()->view('payment.yoomoney', [
            'name' => $checkout->name,
            'email' => $checkout->email,
            'plan' => $plan->code,
            'planName' => $plan->name,
            'amount' => $checkout->amount,
            'paymentId' => $checkout->payment_id,
            'yoomoneyReceiver' => $yoomoneyReceiver,
        ]);
    }
}
