<?php

namespace App\Http\Controllers;

use App\Models\SubscrCheckout;
use App\Models\SubscrPayment;
use App\Models\User;
use App\Services\SubscrSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class YooMoneyNotificationController extends Controller
{
    /**
     * Обработка уведомления YooMoney.
     */
    public function handle(Request $request)
    {
        $data = $request->all();

        \Log::info('YOOMONEY NOTIFICATION', [
            'parameter_keys' => array_keys($data),
            'parameter_count' => count($data),
            'hostname' => gethostname(),
        ]);

        $secret = env('YOOMONEY_NOTIFICATION_SECRET');

        if (!$secret) {
            \Log::error('YOOMONEY NOTIFICATION REJECTED: SECRET MISSING');

            return response('Notification secret is not configured', 500);
        }

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
            \Log::warning('YOOMONEY NOTIFICATION REJECTED: INVALID SIGN');

            return response('Invalid sign', 403);
        }

        $paymentId = $data['label'] ?? null;
        $amount = $data['withdraw_amount'] ?? null;
        $operationId = $data['operation_id'] ?? null;

        if (
            !$paymentId
            || $amount === null
            || $amount === ''
            || !$operationId
        ) {
            \Log::warning('YOOMONEY NOTIFICATION REJECTED: PAYMENT DATA MISSING', [
                'payment_id' => $paymentId,
                'operation_id' => $operationId,
            ]);

            return response('Missing payment data', 400);
        }

        $checkout = SubscrCheckout::query()
            ->where('payment_id', $paymentId)
            ->first();

        if (!$checkout) {
            /*
             * Checkout уже мог быть удалён после успешной оплаты.
             * Проверяем платёж новой системы и при необходимости
             * повторяем отправку Telegram-уведомления.
             */
            $existingPayment = SubscrPayment::query()
                ->where('payment_id', $paymentId)
                ->first();

            if (
                $existingPayment
                && $existingPayment->status === 'success'
                && $existingPayment->subscription_id !== null
            ) {
                $sent = $this->sendTelegramNotification(
                    $existingPayment,
                    (string) $operationId
                );

                if (!$sent) {
                    return response('Telegram notification failed', 500);
                }

                \Log::info(
                    'YOOMONEY DUPLICATE NOTIFICATION: ALREADY PROCESSED',
                    [
                        'payment_id' => $paymentId,
                        'subscr_payment_id' => $existingPayment->id,
                    ]
                );

                return response('OK', 200);
            }

            \Log::error('YOOMONEY CHECKOUT NOT FOUND', [
                'payment_id' => $paymentId,
                'operation_id' => $operationId,
                'existing_subscr_payment_id' => $existingPayment?->id,
                'existing_subscr_payment_status' => $existingPayment?->status,
            ]);

            return response('Checkout not found', 404);
        }

        return $this->processCheckout(
            $checkout,
            $amount,
            (string) $operationId
        );
    }

    /**
     * Обработка оплаты по subscr_checkout.
     */
    private function processCheckout(
        SubscrCheckout $checkout,
        $amount,
        string $operationId
    ) {
        if ((float) $amount !== (float) $checkout->amount) {
            \Log::error('YOOMONEY CHECKOUT INVALID AMOUNT', [
                'checkout_id' => $checkout->id,
                'payment_id' => $checkout->payment_id,
                'expected_amount' => $checkout->amount,
                'received_amount' => $amount,
                'operation_id' => $operationId,
            ]);

            return response('Invalid amount', 400);
        }

        if ($checkout->status !== 'pending') {
            \Log::warning('YOOMONEY CHECKOUT INVALID STATUS', [
                'checkout_id' => $checkout->id,
                'payment_id' => $checkout->payment_id,
                'status' => $checkout->status,
            ]);

            return response('Checkout is not pending', 400);
        }

        if ($checkout->expires_at->lessThanOrEqualTo(now())) {
            \Log::warning('YOOMONEY CHECKOUT EXPIRED', [
                'checkout_id' => $checkout->id,
                'payment_id' => $checkout->payment_id,
                'expires_at' => $checkout->expires_at,
            ]);

            $checkout->delete();

            return response('Checkout expired', 400);
        }

        try {
            $result = DB::transaction(function () use (
                $checkout,
                $amount,
                $operationId
            ) {
                $lockedCheckout = SubscrCheckout::query()
                    ->where('id', $checkout->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedCheckout) {
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

                if ($lockedCheckout->expires_at->lessThanOrEqualTo($now)) {
                    throw new RuntimeException(
                        'Срок действия checkout истёк.'
                    );
                }

                if ((float) $amount !== (float) $lockedCheckout->amount) {
                    throw new RuntimeException(
                        'Сумма уведомления не совпадает с checkout.'
                    );
                }

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

                $user = User::query()
                    ->where('email', $lockedCheckout->email)
                    ->lockForUpdate()
                    ->first();

                if (!$user) {
                    $user = User::create([
                        'name' => $lockedCheckout->name,
                        'email' => $lockedCheckout->email,
                        'password' => Str::random(64),
                    ]);
                }

                $subscriptionPayment = SubscrPayment::create([
                    'user_id' => $user->id,
                    'subscription_id' => null,
                    'plan_id' => $plan->id,
                    'payment_id' => $lockedCheckout->payment_id,
                    'amount' => $lockedCheckout->amount,
                    'status' => 'success',
                    'paid_at' => $now,
                ]);

                $activatedUser = app(
                    SubscrSubscriptionService::class
                )->activate($subscriptionPayment);

                $lockedCheckout->delete();

                return [
                    'already_processed' => false,
                    'user' => $activatedUser->fresh(),
                    'payment_id' => $subscriptionPayment->id,
                    'checkout_id' => $checkout->id,
                ];
            });

            \Log::info('YOOMONEY CHECKOUT PROCESSED', [
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
            ]);

            /*
             * Уведомление отправляется только после завершения
             * транзакции и успешной активации подписки.
             */
            $subscriptionPayment = SubscrPayment::query()
                ->find($result['payment_id']);

            if (
                !$subscriptionPayment
                || !$this->sendTelegramNotification(
                    $subscriptionPayment,
                    $operationId
                )
            ) {
                return response('Telegram notification failed', 500);
            }

            return response('OK', 200);
        } catch (\Throwable $e) {
            \Log::error('YOOMONEY CHECKOUT ACTIVATION FAILED', [
                'checkout_id' => $checkout->id,
                'payment_id' => $checkout->payment_id,
                'operation_id' => $operationId,
                'error' => $e->getMessage(),
            ]);

            return response('Subscription activation failed', 500);
        }
    }

    /**
     * Отправка уведомления в Telegram после успешной активации.
     */
    private function sendTelegramNotification(
        SubscrPayment $payment,
        string $operationId
    ): bool {
        $cacheKey = 'yoomoney_telegram_sent_' . sha1($payment->payment_id);

        try {
            /*
             * Если уведомление об этом платеже уже отправлено,
             * повторно сообщение не отправляем.
             */
            if (Cache::has($cacheKey)) {
                return true;
            }

            $token = env('TELEGRAM_BOT_TOKEN');
            $chatId = env('TELEGRAM_CHAT_ID');

            if (!$token || !$chatId) {
                \Log::error('Telegram notification failed: configuration missing', [
                    'payment_id' => $payment->payment_id,
                    'token_configured' => (bool) $token,
                    'chat_id_configured' => (bool) $chatId,
                ]);

                return false;
            }

            if (
                $payment->status !== 'success'
                || $payment->subscription_id === null
            ) {
                \Log::error('Telegram notification skipped: payment not activated', [
                    'payment_id' => $payment->payment_id,
                    'status' => $payment->status,
                    'subscription_id' => $payment->subscription_id,
                ]);

                return false;
            }

            $user = User::query()->find($payment->user_id);

            $plan = DB::table('plans')
                ->where('id', $payment->plan_id)
                ->first();

            if (!$user || !$plan) {
                \Log::error('Telegram notification failed: user or plan not found', [
                    'payment_id' => $payment->payment_id,
                    'user_found' => (bool) $user,
                    'plan_found' => (bool) $plan,
                ]);

                return false;
            }

            $planName = $plan->name
                ?? $plan->code
                ?? ('ID ' . $plan->id);

            $planCode = $plan->code ?? 'не указан';

            $paidAt = $payment->paid_at
                ? \Illuminate\Support\Carbon::parse($payment->paid_at)
                    ->format('d.m.Y H:i:s')
                : 'не указана';

            $expiresAt = $user->subscription_expires_at
                ? \Illuminate\Support\Carbon::parse(
                    $user->subscription_expires_at
                )->format('d.m.Y H:i:s')
                : 'не указана';

            $message = "✅ Успешная оплата подписки\n\n"
                . "👤 Имя: {$user->name}\n"
                . "📧 E-mail: {$user->email}\n"
                . "🆔 ID пользователя Laravel: {$user->id}\n\n"
                . "📦 Тариф: {$planName} ({$planCode})\n"
                . "💰 Сумма: {$payment->amount} MBT\n"
                . "📅 Дата оплаты: {$paidAt}\n"
                . "⏳ Подписка действует до: {$expiresAt}\n\n"
                . "🧾 Payment ID: {$payment->payment_id}\n"
                . "💳 YooMoney operation ID: {$operationId}\n"
                . "📌 Статус: success";

            $response = Http::asForm()
                ->timeout(10)
                ->post(
                    'https://api.telegram.org/bot' . $token . '/sendMessage',
                    [
                        'chat_id' => $chatId,
                        'text' => $message,
                    ]
                );

            $responseData = $response->json();

            if (
                !$response->successful()
                || !($responseData['ok'] ?? false)
            ) {
                \Log::error('Telegram notification failed', [
                    'payment_id' => $payment->payment_id,
                    'http_status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return false;
            }

            Cache::put($cacheKey, true, now()->addDays(30));

            \Log::info('Telegram notification sent', [
                'payment_id' => $payment->payment_id,
                'telegram_message_id' =>
                    $responseData['result']['message_id'] ?? null,
            ]);

            return true;
        } catch (\Throwable $e) {
            \Log::error('Telegram notification failed', [
                'payment_id' => $payment->payment_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
