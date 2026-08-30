<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DonateController extends Controller
{
    public function select(Request $request)
    {
        $paymentId = $request->get('payment_id');

        if (!$paymentId) {
            abort(404);
        }

        // 🔍 ищем платеж в базе
        $payment = DB::table('payments')
            ->where('payment_id', $paymentId)
            ->first();

        if (!$payment) {
            abort(404);
        }

        // 🔥 выбираем ссылку CloudTips по тарифу
        if ($payment->plan === 'start') {
            $url = 'https://pay.cloudtips.ru/p/7ef07a77';
        } elseif ($payment->plan === 'base') {
            $url = 'https://pay.cloudtips.ru/p/18052abe';
        } elseif ($payment->plan === 'full') {
            $url = 'https://pay.cloudtips.ru/p/70e847a2';
        } else {
            abort(404);
        }

        // 🚀 редирект на нужный донат
        return redirect($url);
    }
}