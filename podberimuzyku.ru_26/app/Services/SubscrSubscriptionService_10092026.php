<?php

namespace App\Services;

use App\Models\SubscrPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscrSubscriptionService
{
    public function activate(SubscrPayment $payment): User
    {
        if ($payment->status !== 'success') {
            throw new RuntimeException(
                'Нельзя активировать подписку: платёж не имеет статуса success.'
            );
        }

        $user = User::find($payment->user_id);

        if (!$user) {
            throw new RuntimeException(
                'Пользователь с ID ' . $payment->user_id . ' не найден.'
            );
        }

        $plan = $this->resolvePlan($payment->plan_id);

        $now = now();

        if (
            $user->subscription_expires_at !== null
            && $user->subscription_expires_at->greaterThan($now)
        ) {
            $subscriptionStart = $user->subscription_expires_at->copy();
        } else {
            $subscriptionStart = $now->copy();
        }

        $subscriptionExpires = $subscriptionStart
            ->copy()
            ->addDays($plan['duration_days']);

        DB::transaction(function () use (
            $user,
            $plan,
            $subscriptionStart,
            $subscriptionExpires
        ) {
            $user->update([
                'subscription_plan' => $plan['code'],
                'subscription_started_at' => $subscriptionStart,
                'subscription_expires_at' => $subscriptionExpires,
            ]);
        });

        app(SubscrWordPressService::class)->updateSubscription(
            $user->email,
            'active'
        );

        return $user->fresh();
    }

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
