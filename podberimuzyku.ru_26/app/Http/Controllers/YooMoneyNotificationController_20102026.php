<?php

namespace App\Http\Controllers;

use App\Models\SubscrCheckout;
use App\Models\SubscrPayment;
use App\Models\User;
use App\Services\SubscrSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class YooMoneyNotificationController extends Controller
{
    /**
     * Обработка уведомления YooMoney.
     *
     * Используется только новая цепочка:
     *
     * subscr_checkout
     * -> subscr_payments
     * -> subscriptions
     * -> users
     * -> WordPress
     *
     * Перехода в старую таблицу payments нет.
     */
    public function handle(Request $request)
    {
        $data = $request->all();

        \Log::info('YOOMONEY NOTIFICATION', [
            'parameter_keys' => array_keys($data),
            'parameter_count' => count($data),
            'hostname' => gethostname(),
        ]);

        /*
         * Проверяем секретное слово.
         */
        $secret = env('YOOMONEY_NOTIFICATION_SECRET');

        if (!$secret) {
            \Log::error(
                'YOOMONEY NOTIFICATION REJECTED: SECRET MISSING'
            );

            return response(
                'Notification secret is not configured',
                500
            );
        }

        /*
         * Проверяем подпись YooMoney.
         */
        $receivedSign = $data['sign'] ?? '';

        if (!$receivedSign) {
            return response('Missing sign', 400);
        }

        unset($data['sign']);
        ksort($data);

        $parts = [];

        foreach ($data as $key => $value) {
            $parts[] = $key . '=' . rawurlencode((string) $value);
        }

        $signString = implode('&', $parts);

        $calculatedSign = hash_hmac(
            'sha256',
            $signString,
            $secret
        );

        if (!hash_equals($calculatedSign, $receivedSign)) {
            \Log::warning(
                'YOOMONEY NOTIFICATION REJECTED: INVALID SIGN'
            );

            return response('Invalid sign', 403);
        }

        /*
         * Получаем данные подтверждённого платежа.
         */
        $paymentId = $data['label'] ?? null;
        $amount = $data['withdraw_amount'] ?? null;
        $operationId = $data['operation_id'] ?? null;

        if (
            !$paymentId
            || $amount === null
            || $amount === ''
            || !$operationId
        ) {
            \Log::warning(
                'YOOMONEY NOTIFICATION REJECTED: PAYMENT DATA MISSING',
                [
                    'payment_id' => $paymentId,
                    'operation_id' => $operationId,
                ]
            );

            return response('Missing payment data', 400);
        }

        /*
         * Ищем checkout новой системы.
         */
        $checkout = SubscrCheckout::query()
            ->where('payment_id', $paymentId)
            ->first();

        if (!$checkout) {
            /*
             * YooMoney может повторно прислать уведомление
             * после успешной обработки, когда checkout уже удалён.
             * В таком случае проверяем историю новой системы.
             */
            $existingPayment = SubscrPayment::query()
                ->where('payment_id', $paymentId)
                ->first();

            if (
                $existingPayment
                && $existingPayment->status === 'success'
                && $existingPayment->subscription_id !== null
            ) {
                \Log::info(
                    'YOOMONEY DUPLICATE NOTIFICATION: ALREADY PROCESSED',
                    [
                        'payment_id' => $paymentId,
                        'subscr_payment_id' => $existingPayment->id,
                    ]
                );

                return response('OK', 200);
            }

            \Log::error(
                'YOOMONEY CHECKOUT NOT FOUND',
                [
                    'payment_id' => $paymentId,
                    'operation_id' => $operationId,
                    'existing_subscr_payment_id' =>
                        $existingPayment?->id,
                    'existing_subscr_payment_status' =>
                        $existingPayment?->status,
                ]
            );

            /*
             * Не обращаемся к старой таблице payments.
             */
            return response('Checkout not found', 404);
        }

        return $this->processCheckout(
            $checkout,
            $amount,
            (string) $operationId
        );
    }

    /**
     * Обрабатывает оплату по subscr_checkout.
     */
    private function processCheckout(
        SubscrCheckout $checkout,
        $amount,
        string $operationId
    ) {
        /*
         * Сначала проверяем сумму.
         */
        if ((float) $amount !== (float) $checkout->amount) {
            \Log::error(
                'YOOMONEY CHECKOUT INVALID AMOUNT',
                [
                    'checkout_id' => $checkout->id,
                    'payment_id' => $checkout->payment_id,
                    'expected_amount' => $checkout->amount,
                    'received_amount' => $amount,
                    'operation_id' => $operationId,
                ]
            );

            return response('Invalid amount', 400);
        }

        if ($checkout->status !== 'pending') {
            \Log::warning(
                'YOOMONEY CHECKOUT INVALID STATUS',
                [
                    'checkout_id' => $checkout->id,
                    'payment_id' => $checkout->payment_id,
                    'status' => $checkout->status,
                ]
            );

            return response('Checkout is not pending', 400);
        }

        if ($checkout->expires_at->lessThanOrEqualTo(now())) {
            \Log::warning(
                'YOOMONEY CHECKOUT EXPIRED',
                [
                    'checkout_id' => $checkout->id,
                    'payment_id' => $checkout->payment_id,
                    'expires_at' => $checkout->expires_at,
                ]
            );

            $checkout->delete();

            return response('Checkout expired', 400);
        }

        try {
            $result = DB::transaction(function () use (
                $checkout,
                $amount,
                $operationId
            ) {
                /*
                 * Блокируем checkout от параллельной обработки.
                 */
                $lockedCheckout = SubscrCheckout::query()
                    ->where('id', $checkout->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedCheckout) {
                    /*
                     * Другой запрос мог уже обработать платёж.
                     */
                    $existingPayment = SubscrPayment::query()
                        ->where('payment_id', $checkout->payment_id)
                        ->first();

                    if (
                        $existingPayment
                        && $existingPayment->status === 'success'
                        && $existingPayment->subscription_id !== null
                    ) {
                        return [
                            'already_processed' => true,
                            'user' => User::find($existingPayment->user_id),
                            'payment_id' => $existingPayment->id,
                        ];
                    }

                    throw new RuntimeException(
                        'Checkout не найден при блокировке.'
                    );
                }

                if ($lockedCheckout->status !== 'pending') {
                    throw new RuntimeException(
                        'Checkout больше не находится в статусе pending.'
                    );
                }

                $now = now();

                if (
                    $lockedCheckout->expires_at
                        ->lessThanOrEqualTo($now)
                ) {
                    throw new RuntimeException(
                        'Срок действия checkout истёк.'
                    );
                }

                if (
                    (float) $amount
                    !== (float) $lockedCheckout->amount
                ) {
                    throw new RuntimeException(
                        'Сумма уведомления не совпадает с checkout.'
                    );
                }

                /*
                 * Проверяем, не был ли этот payment_id
                 * уже использован в истории платежей.
                 */
                $existingPayment = SubscrPayment::query()
                    ->where('payment_id', $lockedCheckout->payment_id)
                    ->lockForUpdate()
                    ->first();

                if ($existingPayment) {
                    if (
                        $existingPayment->status === 'success'
                        && $existingPayment->subscription_id !== null
                    ) {
                        $lockedCheckout->delete();

                        return [
                            'already_processed' => true,
                            'user' => User::find($existingPayment->user_id),
                            'payment_id' => $existingPayment->id,
                        ];
                    }

                    throw new RuntimeException(
                        'Для этого payment_id уже существует платёж, '
                        . 'который не был успешно активирован. '
                        . 'Автоматическое повторное создание запрещено.'
                    );
                }

                /*
                 * Проверяем выбранный тариф.
                 */
                $plan = DB::table('plans')
                    ->where('id', $lockedCheckout->plan_id)
                    ->where('is_active', 1)
                    ->first();

                if (!$plan) {
                    throw new RuntimeException(
                        'Активный тариф для checkout не найден.'
                    );
                }

                if (
                    $plan->duration_days === null
                    || (int) $plan->duration_days < 1
                ) {
                    throw new RuntimeException(
                        'У тарифа не задан корректный duration_days.'
                    );
                }

                /*
                 * Ищем пользователя по e-mail.
                 *
                 * При продлении это существующий пользователь.
                 * Его активная подписка сама по себе не является
                 * причиной отклонять платёж: переход проверяет
                 * SubscrSubscriptionService.
                 */
                $user = User::query()
                    ->where('email', $lockedCheckout->email)
                    ->lockForUpdate()
                    ->first();

                if (!$user) {
                    /*
                     * Нового пользователя создаём только после
                     * подтверждённого уведомления YooMoney.
                     */
                    $user = User::create([
                        'name' => $lockedCheckout->name,
                        'email' => $lockedCheckout->email,
                        'password' => Str::random(64),
                    ]);
                }

                /*
                 * Создаём запись об успешной оплате.
                 * Транзакция откатится, если активация не пройдёт.
                 */
                $subscriptionPayment = SubscrPayment::create([
                    'user_id' => $user->id,
                    'subscription_id' => null,
                    'plan_id' => $plan->id,
                    'payment_id' => $lockedCheckout->payment_id,
                    'amount' => $lockedCheckout->amount,
                    'status' => 'success',
                    'paid_at' => $now,
                ]);

                /*
                 * Общая служба применяет правила перехода,
                 * обновляет subscriptions и пользователя,
                 * а затем синхронизирует WordPress.
                 */
                $activatedUser = app(
                    SubscrSubscriptionService::class
                )->activate($subscriptionPayment);

                /*
                 * Удаляем checkout только после успешной активации.
                 */
                $lockedCheckout->delete();

                return [
                    'already_processed' => false,
                    'user' => $activatedUser->fresh(),
                    'payment_id' => $subscriptionPayment->id,
                    'checkout_id' => $checkout->id,
                ];
            });

            \Log::info(
                'YOOMONEY CHECKOUT PROCESSED',
                [
                    'checkout_id' => $checkout->id,
                    'payment_id' => $checkout->payment_id,
                    'operation_id' => $operationId,
                    'subscr_payment_id' => $result['payment_id'],
                    'already_processed' => $result['already_processed'],
                    'user_id' => $result['user']?->id,
                    'email' => $result['user']?->email,
                    'plan' => $result['user']?->subscription_plan,
                    'subscription_expires_at' =>
                        $result['user']?->subscription_expires_at,
                ]
            );

            return response('OK', 200);
        } catch (\Throwable $e) {
            \Log::error(
                'YOOMONEY CHECKOUT ACTIVATION FAILED',
                [
                    'checkout_id' => $checkout->id,
                    'payment_id' => $checkout->payment_id,
                    'operation_id' => $operationId,
                    'error' => $e->getMessage(),
                ]
            );

            return response('Subscription activation failed', 500);
        }
    }
}
