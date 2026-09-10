<?php

namespace App\Services;

use App\Models\SubscrPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscrSubscriptionService
{
    /**
     * Активирует успешный платёж и создаёт новый период подписки.
     *
     * Логика:
     * - первая покупка начинается с момента активации;
     * - при действующей подписке новый период начинается точно
     *   с текущего subscription_expires_at;
     * - разрешены только переходы вверх/продление;
     * - start после полного окончания подписки доступен только
     *   через 24 часа после точного момента expires_at;
     * - каждый оплаченный период получает отдельную запись
     *   в subscriptions;
     * - subscr_payments.subscription_id связывается с созданной
     *   записью subscriptions;
     * - повторная активация уже обработанного платежа не создаёт
     *   второй период.
     */
    public function activate(SubscrPayment $payment): User
    {
        if ($payment->status !== 'success') {
            throw new RuntimeException(
                'Нельзя активировать подписку: платёж не имеет статуса success.'
            );
        }

        $result = DB::transaction(function () use ($payment) {
            /*
             * Блокируем платёж на время транзакции.
             * Это защищает от двойной обработки одного и того же
             * успешного платежа при параллельных запросах.
             */
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
             * Если этот платёж уже связан с подпиской,
             * повторно ничего не создаём.
             */
            if ($lockedPayment->subscription_id !== null) {
                $existingUser = User::find($lockedPayment->user_id);

                if (!$existingUser) {
                    throw new RuntimeException(
                        'Пользователь с ID ' . $lockedPayment->user_id . ' не найден.'
                    );
                }

                return [
                    'user' => $existingUser->fresh(),
                    'created' => false,
                ];
            }

            /*
             * Блокируем пользователя.
             *
             * Это важно при одновременной обработке двух успешных
             * платежей одного пользователя: второй процесс должен
             * увидеть уже обновлённое subscription_expires_at.
             */
            $user = User::query()
                ->where('id', $lockedPayment->user_id)
                ->lockForUpdate()
                ->first();

            if (!$user) {
                throw new RuntimeException(
                    'Пользователь с ID ' . $lockedPayment->user_id . ' не найден.'
                );
            }

            $plan = $this->resolvePlan((int) $lockedPayment->plan_id);

            $now = now();

            $currentExpiresAt = $user->subscription_expires_at;
            $currentPlanCode = $user->subscription_plan;

            $hasActiveSubscription =
                $currentExpiresAt !== null
                && $currentExpiresAt->greaterThan($now);

            /*
             * Проверяем допустимость выбранного тарифа
             * относительно текущей подписки.
             */
            $this->validatePlanTransition(
                $currentPlanCode,
                $plan['code'],
                $hasActiveSubscription,
                $currentExpiresAt,
                $now
            );

            /*
             * Определяем начало нового периода.
             *
             * Если текущая подписка ещё действует —
             * продолжаем её без потери времени.
             *
             * Если подписка закончилась —
             * начинаем новый период с момента активации.
             */
            if ($hasActiveSubscription) {
                $subscriptionStart = $currentExpiresAt->copy();
            } else {
                $subscriptionStart = $now->copy();
            }

            $subscriptionExpires = $subscriptionStart
                ->copy()
                ->addDays($plan['duration_days']);

            /*
             * Если существовал предыдущий период, который уже
             * закончился, помечаем его как expired.
             *
             * При продлении действующей подписки старый период
             * заканчивается ровно в момент начала нового.
             */
            if ($currentExpiresAt !== null) {
                DB::table('subscriptions')
                    ->where('user_id', $user->id)
                    ->where('expires_at', '<=', $subscriptionStart)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'expired',
                        'updated_at' => $now,
                    ]);
            }

            /*
             * Создаём отдельную запись истории подписки.
             */
            $subscriptionId = DB::table('subscriptions')->insertGetId([
                'user_id' => $user->id,
                'plan_id' => $plan['id'],
                'status' => 'active',
                'started_at' => $subscriptionStart,
                'expires_at' => $subscriptionExpires,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            /*
             * Обновляем текущее состояние пользователя.
             */
            $user->update([
                'subscription_plan' => $plan['code'],
                'subscription_started_at' => $subscriptionStart,
                'subscription_expires_at' => $subscriptionExpires,
            ]);

            /*
             * Связываем платёж с конкретным периодом подписки.
             */
            $lockedPayment->update([
                'subscription_id' => $subscriptionId,
            ]);

            return [
                'user' => $user->fresh(),
                'created' => true,
            ];
        });

        /*
         * WordPress обновляем только после успешного завершения
         * транзакции Laravel.
         *
         * В случае ошибки WP данные Laravel уже остаются
         * консистентными, а сама активация не откатывается.
         */
        if ($result['created']) {
            app(SubscrWordPressService::class)->updateSubscription(
                $result['user']->email,
                'active'
            );
        }

        return $result['user'];
    }

    /**
     * Проверяет допустимость перехода с текущего тарифа
     * на новый тариф.
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
         * Пока подписка действует, разрешены:
         *
         * start -> start/base/full
         * base  -> base/full
         * full  -> full
         */
        if ($hasActiveSubscription) {
            $allowedTransitions = [
                'start' => ['start', 'base', 'full'],
                'base' => ['base', 'full'],
                'full' => ['full'],
            ];

            /*
             * Технический test не участвует в обычных правилах
             * продления.
             */
            if ($currentPlanCode === 'test') {
                return;
            }

            if (
                !isset($allowedTransitions[$currentPlanCode])
                || !in_array(
                    $newPlanCode,
                    $allowedTransitions[$currentPlanCode],
                    true
                )
            ) {
                throw new RuntimeException(
                    'Переход с тарифа "' . $currentPlanCode
                    . '" на тариф "' . $newPlanCode
                    . '" запрещён.'
                );
            }

            return;
        }

        /*
         * Подписка уже закончилась.
         *
         * start можно купить только через 24 часа после
         * точного момента окончания предыдущей подписки.
         *
         * base/full доступны сразу.
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
     *
     * Длительность берётся только из plans.duration_days.
     */
    private function resolvePlan(int $planId): array
    {
        $plan = DB::table('plans')
            ->where('id', $planId)
            ->where('is_active', 1)
            ->first();

        if (!$plan) {
            throw new RuntimeException(
                'Активный тариф с plan_id ' . $planId . ' не найден.'
            );
        }

        if ($plan->duration_days === null) {
            throw new RuntimeException(
                'У тарифа "' . $plan->name . '" не задан duration_days.'
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
