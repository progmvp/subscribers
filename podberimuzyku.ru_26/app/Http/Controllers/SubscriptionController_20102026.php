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
     * Срок действия checkout для повторного открытия оплаты.
     *
     * Истечение срока не удаляет заказ и не запрещает
     * обработку позднего уведомления YooMoney.
     */
    private const CHECKOUT_TTL_MINUTES = 20;

    /**
     * Публичная форма оформления подписки.
     */
    public function show()
    {
        $plans = DB::table('plans')
            ->where('is_active', 1)
            ->whereIn('code', ['test', 'start', 'base', 'full'])
            ->orderBy('id')
            ->get();

        return view('subscription.subscribe', [
            'plans' => $plans,
        ]);
    }

    /**
     * Создаёт checkout для публичной покупки.
     *
     * До подтверждения оплаты создаётся только subscr_checkout.
     * Пользователь и платёж создаются в обработчике уведомления
     * YooMoney после подтверждения успешной оплаты.
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
                        ->whereIn('code', [
                            'test',
                            'start',
                            'base',
                            'full',
                        ]);
                }),
            ],
        ]);

        $name = trim($validated['name']);
        $email = mb_strtolower(trim($validated['email']));
        $planId = (int) $validated['plan_id'];

        $plan = DB::table('plans')
            ->where('id', $planId)
            ->where('is_active', 1)
            ->whereIn('code', ['test', 'start', 'base', 'full'])
            ->first();

        if (!$plan) {
            return back()
                ->withInput()
                ->withErrors([
                    'plan_id' => 'Выбранный тариф недоступен.',
                ]);
        }

        if (
            $plan->duration_days === null
            || (int) $plan->duration_days < 1
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'plan_id' => 'У выбранного тарифа не задан срок действия.',
                ]);
        }

        $result = DB::transaction(function () use (
            $name,
            $email,
            $plan
        ) {
            $now = now();

            /*
             * Находим существующего пользователя.
             *
             * Существующий аккаунт повторно не создаётся.
             * Блокировка нужна для согласованной проверки подписки.
             */
            $user = User::query()
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            /*
             * Проверяем допустимость выбранного тарифа,
             * если у пользователя уже есть активная подписка.
             *
             * Тестовый тариф оставляем исключением.
             */
            if (
                $user
                && $user->subscription_plan !== null
                && $user->subscription_expires_at !== null
                && $user->subscription_expires_at->greaterThan($now)
            ) {
                $allowedTransitions = [
                    'start' => ['start', 'base', 'full'],
                    'base' => ['base', 'full'],
                    'full' => ['full'],
                    'test' => ['test', 'start', 'base', 'full'],
                ];

                $isTestPlan = $plan->code === 'test';

                if (
                    !$isTestPlan
                    && (
                        !isset(
                            $allowedTransitions[$user->subscription_plan]
                        )
                        || !in_array(
                            $plan->code,
                            $allowedTransitions[$user->subscription_plan],
                            true
                        )
                    )
                ) {
                    return [
                        'type' => 'transition_unavailable',
                        'current_plan' => $user->subscription_plan,
                        'selected_plan' => $plan->code,
                    ];
                }
            }

            /*
             * После окончания подписки тариф start доступен
             * только через 24 часа после точного времени окончания.
             * Тарифы base и full доступны сразу.
             */
            if (
                $user
                && $user->subscription_expires_at !== null
                && $user->subscription_expires_at->lessThanOrEqualTo($now)
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
             * Ищем только действующий checkout для этого e-mail
             * и этого же тарифа.
             *
             * Просроченные заказы и заказы с другими тарифами
             * не удаляем: YooMoney может прислать позднее уведомление.
             */
            $existingCheckout = SubscrCheckout::query()
                ->where('email', $email)
                ->where('plan_id', $plan->id)
                ->where('status', 'pending')
                ->where('expires_at', '>', $now)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existingCheckout) {
                return [
                    'type' => 'checkout',
                    'checkout' => $existingCheckout,
                    'plan' => $plan,
                ];
            }

            /*
             * Для существующего пользователя используем имя аккаунта,
             * а не имя, случайно введённое в публичную форму.
             */
            $checkoutName = $user ? $user->name : $name;

            /*
             * Создаём новый checkout.
             *
             * Не удаляем старые заказы и не создаём здесь
             * записи в users или subscr_payments.
             */
            $checkout = SubscrCheckout::create([
                'name' => $checkoutName,
                'email' => $email,
                'plan_id' => $plan->id,
                'payment_id' => (string) Str::uuid(),
                'amount' => $plan->price,
                'status' => 'pending',
                'expires_at' => $now
                    ->copy()
                    ->addMinutes(self::CHECKOUT_TTL_MINUTES),
            ]);

            return [
                'type' => 'checkout',
                'checkout' => $checkout,
                'plan' => $plan,
            ];
        });

        if ($result['type'] === 'transition_unavailable') {
            return back()
                ->withInput()
                ->withErrors([
                    'plan_id' =>
                        'Переход с тарифа «'
                        . $result['current_plan']
                        . '» на тариф «'
                        . $result['selected_plan']
                        . '» запрещён правилами подписки.',
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
