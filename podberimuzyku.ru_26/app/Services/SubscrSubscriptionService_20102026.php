<?php

namespace App\Services;

use App\Models\SubscrPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscrSubscriptionService
{
    /**
     * Активирует успешный платёж.
     *
     * Для одного пользователя используется одна строка subscriptions.
     * История платежей хранится в subscr_payments.
     */
    public function activate(SubscrPayment $payment): User
    {
        if ($payment->status !== 'success') {
            throw new RuntimeException(
                'Нельзя активировать подписку: платёж не имеет статуса success.'
            );
        }

        $result = DB::transaction(function () use ($payment) {
            $lockedPayment = SubscrPayment::query()
                ->where('id', $payment->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedPayment) {
                throw new RuntimeException(
                    'Платёж с ID ' . $payment->id . ' не найден.'
                );
            }

            if ($lockedPayment->status !== 'success') {
                throw new RuntimeException(
                    'Нельзя активировать подписку: платёж не имеет статуса success.'
                );
            }

            /*
             * Повторная активация уже обработанного платежа
             * не должна менять подписку.
             */
            if ($lockedPayment->subscription_id !== null) {
                $existingUser = User::find($lockedPayment->user_id);

                if (!$existingUser) {
                    throw new RuntimeException(
                        'Пользователь с ID '
                        . $lockedPayment->user_id
                        . ' не найден.'
                    );
                }

                return [
                    'user' => $existingUser->fresh(),
                    'changed' => false,
                ];
            }

            $user = User::query()
                ->where('id', $lockedPayment->user_id)
                ->lockForUpdate()
                ->first();

            if (!$user) {
                throw new RuntimeException(
                    'Пользователь с ID '
                    . $lockedPayment->user_id
                    . ' не найден.'
                );
            }

            $plan = $this->resolvePlan(
                (int) $lockedPayment->plan_id
            );

            $now = now();

            $currentExpiresAt = $user->subscription_expires_at;
            $currentPlanCode = $user->subscription_plan;

            $hasActiveSubscription =
                $currentExpiresAt !== null
                && $currentExpiresAt->greaterThan($now);

            $this->validatePlanTransition(
                $currentPlanCode,
                $plan['code'],
                $hasActiveSubscription,
                $currentExpiresAt,
                $now
            );

            /*
             * При активной подписке новый период начинается
             * точно в момент окончания текущего периода.
             *
             * При завершённой подписке — с момента активации.
             */
            if ($hasActiveSubscription) {
                $subscriptionStart = $currentExpiresAt->copy();
            } else {
                $subscriptionStart = $now->copy();
            }

            /*
             * Срок определяется только plans.duration_days.
             */
            $subscriptionExpires = $subscriptionStart
                ->copy()
                ->addDays($plan['duration_days']);

            $subscription = DB::table('subscriptions')
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($subscription) {
                DB::table('subscriptions')
                    ->where('id', $subscription->id)
                    ->update([
                        'plan_id' => $plan['id'],
                        'status' => 'active',
                        'started_at' => $subscriptionStart,
                        'expires_at' => $subscriptionExpires,
                        'updated_at' => $now,
                    ]);

                $subscriptionId = (int) $subscription->id;
            } else {
                $subscriptionId = (int) DB::table('subscriptions')
                    ->insertGetId([
                        'user_id' => $user->id,
                        'plan_id' => $plan['id'],
                        'status' => 'active',
                        'started_at' => $subscriptionStart,
                        'expires_at' => $subscriptionExpires,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
            }

            $user->update([
                'subscription_plan' => $plan['code'],
                'subscription_started_at' => $subscriptionStart,
                'subscription_expires_at' => $subscriptionExpires,
            ]);

            $lockedPayment->update([
                'subscription_id' => $subscriptionId,
            ]);

            return [
                'user' => $user->fresh(),
                'changed' => true,
            ];
        });

        /*
         * Синхронизация WordPress выполняется после транзакции Laravel.
         */
        if ($result['changed']) {
            app(SubscrWordPressService::class)->updateSubscription(
                $result['user']->email,
                'active'
            );
        }

        return $result['user'];
    }

    /**
     * Проверяет допустимость перехода между тарифами.
     */
    private function validatePlanTransition(
        ?string $currentPlanCode,
        string $newPlanCode,
        bool $hasActiveSubscription,
        $currentExpiresAt,
        $now
    ): void {
        /*
         * Первая покупка.
         */
        if ($currentPlanCode === null || $currentExpiresAt === null) {
            return;
        }

        /*
         * Тестовый тариф — исключение:
         * на него разрешён переход при продлении из ЛК,
         * в том числе с тарифа более высокого уровня.
         */
        if ($newPlanCode === 'test') {
            return;
        }

        if ($hasActiveSubscription) {
            $allowedTransitions = [
                'start' => ['start', 'base', 'full'],
                'base' => ['base', 'full'],
                'full' => ['full'],
                'test' => ['test', 'start', 'base', 'full'],
            ];

            if (
                !isset($allowedTransitions[$currentPlanCode])
                || !in_array(
                    $newPlanCode,
                    $allowedTransitions[$currentPlanCode],
                    true
                )
            ) {
                throw new RuntimeException(
                    'Переход с тарифа "'
                    . $currentPlanCode
                    . '" на тариф "'
                    . $newPlanCode
                    . '" запрещён.'
                );
            }

            return;
        }

        /*
         * После окончания подписки тариф start доступен
         * только через 24 часа после точного времени окончания.
         * Тарифы base и full доступны сразу.
         */
        if (
            $newPlanCode === 'start'
            && $currentExpiresAt->copy()->addHours(24)->greaterThan($now)
        ) {
            $availableAt = $currentExpiresAt->copy()->addHours(24);

            throw new RuntimeException(
                'Тариф "start" будет доступен после '
                . $availableAt->format('d.m.Y H:i:s')
                . '.'
            );
        }
    }

    /**
     * Получает активный тариф из таблицы plans.
     */
    private function resolvePlan(int $planId): array
    {
        $plan = DB::table('plans')
            ->where('id', $planId)
            ->where('is_active', 1)
            ->first();

        if (!$plan) {
            throw new RuntimeException(
                'Активный тариф с plan_id '
                . $planId
                . ' не найден.'
            );
        }

        if (
            $plan->duration_days === null
            || (int) $plan->duration_days < 1
        ) {
            throw new RuntimeException(
                'У тарифа "'
                . $plan->name
                . '" не задан корректный duration_days.'
            );
        }

        return [
            'id' => (int) $plan->id,
            'code' => $plan->code,
            'name' => $plan->name,
            'duration_days' => (int) $plan->duration_days,
        ];
    }
}
