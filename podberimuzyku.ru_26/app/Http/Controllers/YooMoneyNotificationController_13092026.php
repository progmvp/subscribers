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
     * Обработка HTTP Notification от YooMoney.
     *
     * Один endpoint обслуживает две независимые цепочки:
     *
     * 1. Новая Laravel-система:
     *
     * subscr_checkout
     * -> users
     * -> subscr_payments
     * -> subscriptions
     * -> users.subscription_*
     * -> WordPress
     *
     * 2. Старая система:
     *
     * payments
     * -> Telegram
     * -> confirm-payment.php
     *
     * Старая цепочка намеренно не изменяется.
     */
    public function handle(Request $request)
    {
        $data = $request->all();

        \Log::info('YOOMONEY NOTIFICATION', $data);

        /*
        |--------------------------------------------------------------------------
        | Секретное слово YooMoney
        |--------------------------------------------------------------------------
        */

        $secret = env('YOOMONEY_NOTIFICATION_SECRET');

        if (!$secret) {
            return response(
                'Notification secret is not configured',
                500
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Проверка подписи sign
        |--------------------------------------------------------------------------
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
            return response('Invalid sign', 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Данные платежа
        |--------------------------------------------------------------------------
        */

        $paymentId = $data['label'] ?? null;
        $amount = $data['withdraw_amount'] ?? null;
        $operationId = $data['operation_id'] ?? null;

        if (!$paymentId || !$amount || !$operationId) {
            return response('Missing payment data', 400);
        }

        /*
        |--------------------------------------------------------------------------
        | НОВАЯ ЦЕПОЧКА — subscr_checkout
        |--------------------------------------------------------------------------
        */

        $checkout = SubscrCheckout::query()
            ->where('payment_id', $paymentId)
            ->first();

        if ($checkout) {
            return $this->processCheckout(
                $checkout,
                $amount,
                $operationId
            );
        }

        /*
        |--------------------------------------------------------------------------
        | СТАРАЯ ЦЕПОЧКА — payments
        |--------------------------------------------------------------------------
        |
        | Здесь оставляем существующий старый механизм.
        |
        */

        return $this->processLegacyPayment(
            $paymentId,
            $amount,
            $operationId
        );
    }

    /**
     * Обработка новой покупки через subscr_checkout.
     */
    private function processCheckout(
        SubscrCheckout $checkout,
        $amount,
        string $operationId
    ) {
        /*
        |--------------------------------------------------------------------------
        | Проверка суммы до изменения данных
        |--------------------------------------------------------------------------
        */

        if ((float) $amount !== (float) $checkout->amount) {
            \Log::warning(
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

        /*
        |--------------------------------------------------------------------------
        | Повторное уведомление
        |--------------------------------------------------------------------------
        |
        | После успешной обработки checkout удаляется.
        | Поэтому нормальный повторный callback сюда уже не попадёт.
        |
        */

        if ($checkout->status !== 'pending') {
            \Log::warning(
                'YOOMONEY CHECKOUT INVALID STATUS',
                [
                    'checkout_id' => $checkout->id,
                    'payment_id' => $checkout->payment_id,
                    'status' => $checkout->status,
                    'operation_id' => $operationId,
                ]
            );

            return response('Checkout is not pending', 400);
        }

        /*
        |--------------------------------------------------------------------------
        | Проверка срока действия checkout
        |--------------------------------------------------------------------------
        |
        | Checkout действует ровно 20 минут.
        |
        */

        $now = now();

        if ($checkout->expires_at->lessThanOrEqualTo($now)) {
            \Log::warning(
                'YOOMONEY CHECKOUT EXPIRED',
                [
                    'checkout_id' => $checkout->id,
                    'payment_id' => $checkout->payment_id,
                    'expires_at' => $checkout->expires_at,
                    'operation_id' => $operationId,
                ]
            );

            $checkout->delete();

            return response('Checkout expired', 400);
        }

        /*
        |--------------------------------------------------------------------------
        | Атомарная активация
        |--------------------------------------------------------------------------
        |
        | В одной транзакции:
        |
        | 1. блокируем checkout;
        | 2. повторно проверяем его;
        | 3. ищем/создаём User;
        | 4. создаём subscr_payments;
        | 5. создаём subscriptions;
        | 6. обновляем users.subscription_*;
        | 7. удаляем checkout.
        |
        */

        try {
            $result = DB::transaction(
                function () use (
                    $checkout,
                    $amount,
                    $operationId
                ) {
                    $now = now();

                    /*
                    |--------------------------------------------------------------------------
                    | Блокируем checkout
                    |--------------------------------------------------------------------------
                    */

                    $lockedCheckout = SubscrCheckout::query()
                        ->where('id', $checkout->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedCheckout) {
                        throw new RuntimeException(
                            'Checkout с ID ' .
                            $checkout->id .
                            ' не найден.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Повторные проверки внутри транзакции
                    |--------------------------------------------------------------------------
                    */

                    if ($lockedCheckout->status !== 'pending') {
                        throw new RuntimeException(
                            'Checkout уже не находится в статусе pending.'
                        );
                    }

                    if (
                        $lockedCheckout->expires_at
                            ->lessThanOrEqualTo($now)
                    ) {
                        throw new RuntimeException(
                            'Checkout истёк до завершения обработки.'
                        );
                    }

                    if (
                        (float) $amount !==
                        (float) $lockedCheckout->amount
                    ) {
                        throw new RuntimeException(
                            'Сумма YooMoney не совпадает с checkout.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Проверяем пользователя
                    |--------------------------------------------------------------------------
                    */

                    $user = User::query()
                        ->where('email', $lockedCheckout->email)
                        ->lockForUpdate()
                        ->first();

                    /*
                    |--------------------------------------------------------------------------
                    | Если пользователь уже существует
                    |--------------------------------------------------------------------------
                    */

                    if ($user) {
                        $currentExpiresAt =
                            $user->subscription_expires_at;

                        if (
                            $currentExpiresAt !== null &&
                            $currentExpiresAt->greaterThan($now)
                        ) {
                            throw new RuntimeException(
                                'У пользователя уже имеется активная подписка.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Для уже существующего пользователя:
                        |
                        | start после окончания доступен только через 24 часа.
                        | base/full доступны сразу.
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $currentExpiresAt !== null &&
                            $currentExpiresAt->isPast() &&
                            $lockedCheckout->plan_id
                        ) {
                            $plan = DB::table('plans')
                                ->where(
                                    'id',
                                    $lockedCheckout->plan_id
                                )
                                ->where('is_active', 1)
                                ->first();

                            if (!$plan) {
                                throw new RuntimeException(
                                    'Тариф для checkout не найден.'
                                );
                            }

                            if ($plan->code === 'start') {
                                $startAvailableAt =
                                    $currentExpiresAt
                                        ->copy()
                                        ->addHours(24);

                                if (
                                    $startAvailableAt
                                        ->greaterThan($now)
                                ) {
                                    throw new RuntimeException(
                                        'Тариф start пока недоступен. ' .
                                        'Доступен после ' .
                                        $startAvailableAt->format(
                                            'd.m.Y H:i:s'
                                        ) .
                                        '.'
                                    );
                                }
                            }
                        }
                    } else {
                        /*
                        |--------------------------------------------------------------------------
                        | НОВЫЙ ПОЛЬЗОВАТЕЛЬ
                        |--------------------------------------------------------------------------
                        |
                        | Именно здесь, только после подтверждённой оплаты,
                        | впервые создаём постоянного Laravel-пользователя.
                        |
                        */

                        $user = User::create(
                            [
                                'name' => $lockedCheckout->name,
                                'email' => $lockedCheckout->email,
                                'password' => Str::random(64),
                            ]
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Проверяем тариф
                    |--------------------------------------------------------------------------
                    */

                    $plan = DB::table('plans')
                        ->where(
                            'id',
                            $lockedCheckout->plan_id
                        )
                        ->where('is_active', 1)
                        ->first();

                    if (!$plan) {
                        throw new RuntimeException(
                            'Активный тариф с ID ' .
                            $lockedCheckout->plan_id .
                            ' не найден.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Создаём subscr_payments
                    |--------------------------------------------------------------------------
                    */

                    $subscriptionPayment = SubscrPayment::create(
                        [
                            'user_id' => $user->id,
                            'subscription_id' => null,
                            'plan_id' => $plan->id,
                            'payment_id' =>
                                $lockedCheckout->payment_id,
                            'amount' => $lockedCheckout->amount,
                            'status' => 'success',
                            'paid_at' => $now,
                        ]
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Активируем подписку
                    |--------------------------------------------------------------------------
                    */

                    $subscriptionService =
                        app(SubscrSubscriptionService::class);

                    $activatedUser =
                        $subscriptionService->activate(
                            $subscriptionPayment
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Удаляем checkout
                    |--------------------------------------------------------------------------
                    */

                    $lockedCheckout->delete();

                    return [
                        'user' => $activatedUser->fresh(),
                        'payment_id' => $subscriptionPayment->id,
                        'checkout_id' => $lockedCheckout->id,
                        'operation_id' => $operationId,
                    ];
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Laravel успешно завершил транзакцию
            |--------------------------------------------------------------------------
            */

            \Log::info(
                'YOOMONEY CHECKOUT ACTIVATED',
                [
                    'checkout_id' =>
                        $result['checkout_id'],

                    'subscr_payment_id' =>
                        $result['payment_id'],

                    'payment_id' =>
                        $checkout->payment_id,

                    'operation_id' =>
                        $result['operation_id'],

                    'user_id' =>
                        $result['user']->id,

                    'email' =>
                        $result['user']->email,

                    'plan' =>
                        $result['user']->subscription_plan,

                    'subscription_started_at' =>
                        $result['user']->subscription_started_at,

                    'subscription_expires_at' =>
                        $result['user']->subscription_expires_at,
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

            return response(
                'Subscription activation failed',
                500
            );
        }
    }

    /**
     * Старая цепочка payments.
     *
     * Не изменяет новую систему подписок.
     */
    private function processLegacyPayment(
        string $paymentId,
        $amount,
        string $operationId
    ) {
        $payment = DB::table('payments')
            ->where('payment_id', $paymentId)
            ->first();

        if (!$payment) {
            return response('Payment not found', 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Проверяем сумму
        |--------------------------------------------------------------------------
        */

        if ((float) $amount !== (float) $payment->amount) {
            return response('Invalid amount', 400);
        }

        /*
        |--------------------------------------------------------------------------
        | Если уже оплачено
        |--------------------------------------------------------------------------
        */

        if ($payment->status === 'success') {
            return response('OK', 200);
        }

        /*
        |--------------------------------------------------------------------------
        | Старый платёж успешно подтверждён
        |--------------------------------------------------------------------------
        */

        DB::table('payments')
            ->where('id', $payment->id)
            ->update(
                [
                    'status' => 'success',
                    'updated_at' => now(),
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | TELEGRAM — СТАРАЯ ЦЕПОЧКА
        |--------------------------------------------------------------------------
        */

        $token = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        $text =
            "✅ Успешная оплата\n\n" .
            "👤 {$payment->name}\n" .
            "📧 {$payment->email}\n\n" .
            "📦 {$payment->plan}\n" .
            "💰 {$payment->amount} ₽\n\n" .
            "🆔 {$payment->payment_id}\n" .
            "💳 YooMoney\n" .
            "📌 Статус: success";

        $url =
            "https://api.telegram.org/bot{$token}/sendMessage";

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt(
            $ch,
            CURLOPT_RETURNTRANSFER,
            true
        );

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            [
                'chat_id' => $chatId,
                'text' => $text,
            ]
        );

        $telegramResponse = curl_exec($ch);

        if ($telegramResponse === false) {
            \Log::error(
                'Telegram notification failed',
                [
                    'error' => curl_error($ch),
                ]
            );
        } else {
            \Log::info(
                'Telegram notification sent',
                [
                    'response' => $telegramResponse,
                ]
            );
        }

        curl_close($ch);

        /*
        |--------------------------------------------------------------------------
        | Передаём подтверждение на PODBERIMUZYKU.RU
        |--------------------------------------------------------------------------
        */

        $confirmData = [
            'email' => $payment->email,
            'plan' => $payment->plan,
            'status' => 'confirmed',
        ];

        $ch = curl_init(
            'https://podberimuzyku.ru/billing/confirm-payment.php'
        );

        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            'PODBERIMUZYKU-YOOMONEY/1.0'
        );

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt(
            $ch,
            CURLOPT_RETURNTRANSFER,
            true
        );

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'X-MBT-SECRET: ' . env('MBT_SECRET'),
            ]
        );

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            json_encode($confirmData)
        );

        curl_exec($ch);

        curl_close($ch);

        return response('OK', 200);
    }
}
