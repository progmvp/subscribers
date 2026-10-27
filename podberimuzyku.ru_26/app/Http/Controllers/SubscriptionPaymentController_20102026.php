<?php

namespace App\Http\Controllers;

use App\Models\SubscrCheckout;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionPaymentController extends Controller
{
    /**
     * Срок использования checkout для повторного открытия оплаты.
     * Истечение срока не аннулирует заказ и не запрещает обработку
     * уведомления YooMoney.
     */
    private const CHECKOUT_TTL_MINUTES = 20;

    /**
     * Создание платежа для продления подписки из ЛК.
     *
     * До подтверждения YooMoney создаётся только subscr_checkout.
     * Записи subscr_payments и активация подписки выполняются
     * после подтверждения платежа.
     */
    public function create(Request $request)
    {
        $authenticatedUser = Auth::user();

        if (!$authenticatedUser) {
            abort(403);
        }

        $validated = $request->validate([
            'plan_id' => ['required', 'integer'],
        ]);

        $planId = (int) $validated['plan_id'];

        $result = DB::transaction(function () use (
            $authenticatedUser,
            $planId
        ) {
            $now = now();

            /*
             * Блокируем пользователя, чтобы проверить актуальную
             * подписку перед созданием нового платежа.
             */
            $user = User::query()
                ->where('id', $authenticatedUser->id)
                ->lockForUpdate()
                ->first();

            if (!$user) {
                abort(403);
            }

            /*
             * Получаем выбранный активный тариф.
             */
            $plan = DB::table('plans')
                ->where('id', $planId)
                ->where('is_active', 1)
                ->first();

            if (!$plan) {
                return [
                    'error' => 'Выбранный тариф не найден или неактивен.',
                ];
            }

            if (
                $plan->duration_days === null
                || (int) $plan->duration_days < 1
            ) {
                return [
                    'error' => 'У выбранного тарифа не задан срок действия.',
                ];
            }

            /*
             * Проверяем правила перехода до оплаты.
             */
            $currentPlanCode = $user->subscription_plan;
            $currentExpiresAt = $user->subscription_expires_at;

            $hasActiveSubscription =
                $currentExpiresAt !== null
                && $currentExpiresAt->greaterThan($now);

            if (
                $currentPlanCode !== null
                && $currentExpiresAt !== null
                && $hasActiveSubscription
            ) {
                /*
                 * Тестовый тариф разрешён как исключение:
                 * на него можно перейти при продлении из ЛК.
                 */
                $allowedTransitions = [
                    'start' => ['start', 'base', 'full'],
                    'base' => ['base', 'full'],
                    'full' => ['full'],
                    'test' => ['test', 'start', 'base', 'full'],
                ];

                $isTestRenewal = $plan->code === 'test';

                if (
                    !$isTestRenewal
                    && (
                        !isset($allowedTransitions[$currentPlanCode])
                        || !in_array(
                            $plan->code,
                            $allowedTransitions[$currentPlanCode],
                            true
                        )
                    )
                ) {
                    return [
                        'error' =>
                            'Переход с тарифа «'
                            . $currentPlanCode
                            . '» на тариф «'
                            . $plan->code
                            . '» запрещён правилами подписки.',
                    ];
                }
            }

            /*
             * Если подписка закончилась, тариф start доступен
             * только через 24 часа после точного времени окончания.
             * Тарифы base и full доступны сразу.
             */
            if (
                $currentExpiresAt !== null
                && !$hasActiveSubscription
                && $plan->code === 'start'
            ) {
                $startAvailableAt = $currentExpiresAt
                    ->copy()
                    ->addHours(24);

                if ($startAvailableAt->greaterThan($now)) {
                    return [
                        'error' =>
                            'Тариф «Стартовый» будет доступен после '
                            . $startAvailableAt->format('d.m.Y H:i:s')
                            . '.',
                    ];
                }
            }

            /*
             * Ищем только неистёкший checkout для того же тарифа.
             *
             * ВАЖНО:
             * - просроченные checkout не удаляем;
             * - checkout других тарифов не удаляем;
             * - у каждого нового заказа свой payment_id.
             *
             * Если YooMoney задержит уведомление об уже выполненной
             * оплате, контроллер уведомлений сможет найти старый заказ.
             */
            $existingCheckout = SubscrCheckout::query()
                ->where('email', $user->email)
                ->where('plan_id', $plan->id)
                ->where('status', 'pending')
                ->where('expires_at', '>', $now)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existingCheckout) {
                return [
                    'checkout' => $existingCheckout,
                    'plan' => $plan,
                ];
            }

            /*
             * Создаём новый checkout без создания subscr_payments.
             * Старые pending-заказы сохраняются для обработки
             * возможных уведомлений YooMoney.
             */
            $checkout = SubscrCheckout::create([
                'name' => $user->name,
                'email' => $user->email,
                'plan_id' => $plan->id,
                'payment_id' => (string) Str::uuid(),
                'amount' => $plan->price,
                'status' => 'pending',
                'expires_at' => $now
                    ->copy()
                    ->addMinutes(self::CHECKOUT_TTL_MINUTES),
            ]);

            return [
                'checkout' => $checkout,
                'plan' => $plan,
            ];
        });

        /*
         * Если проверка правил не пройдена, платёж не создаём.
         */
        if (isset($result['error'])) {
            return back()->withErrors([
                'plan_id' => $result['error'],
            ]);
        }

        $yoomoneyReceiver = env('YOOMONEY_RECEIVER');

        if (!$yoomoneyReceiver) {
            abort(500, 'YOOMONEY_RECEIVER is not configured');
        }

        $checkout = $result['checkout'];
        $plan = $result['plan'];

        /*
         * Используем существующую страницу оплаты YooMoney.
         */
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
