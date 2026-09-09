<?php

namespace App\Http\Controllers;

use App\Models\SubscrPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionPaymentController extends Controller
{
    public function create(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        $validated = $request->validate([
            'plan_id' => ['required', 'integer'],
        ]);

        /*
         * Получаем тариф из таблицы plans.
         */
        $plan = DB::table('plans')
            ->where('id', $validated['plan_id'])
            ->where('is_active', 1)
            ->first();

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Тариф не найден или неактивен.',
            ], 400);
        }

        /*
         * Уникальный ID операции.
         */
        $paymentId = (string) Str::uuid();

        /*
         * Создаём запись в новой таблице subscr_payments.
         */
        $payment = SubscrPayment::create([
            'user_id' => $user->id,
            'subscription_id' => null,
            'plan_id' => $plan->id,
            'payment_id' => $paymentId,
            'amount' => $plan->price,
            'status' => 'pending',
            'paid_at' => null,
        ]);

        /*
         * YooMoney.
         */
        $yoomoneyReceiver = env('YOOMONEY_RECEIVER');

        if (!$yoomoneyReceiver) {
            abort(500, 'YOOMONEY_RECEIVER is not configured');
        }

        return response()->view('payment.yoomoney', [
            'name' => $user->name,
            'email' => $user->email,
            'plan' => $plan->code,
            'planName' => $plan->name,
            'amount' => $plan->price,
            'paymentId' => $payment->payment_id,
            'yoomoneyReceiver' => $yoomoneyReceiver,
        ]);
    }
}
