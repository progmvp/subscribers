<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPaymentController extends Controller
{
    public function index()
    {
        $payments = DB::table('payments')
            ->orderBy('id', 'desc')
            ->get();

        return view('payments', compact('payments'));
    }

    public function confirm($id)
    {
        $payment = DB::table('payments')
            ->where('id', $id)
            ->first();

        if (!$payment) {
            abort(404);
        }

        // Меняем статус локально
        DB::table('payments')
            ->where('id', $id)
            ->update([
                'status' => 'success',
                'updated_at' => now()
            ]);

        // Отправляем JSON на podberimuzyku.ru
        $data = [
            'email' => $payment->email,
            'plan' => $payment->plan,
            'status' => 'confirmed'
        ];

        $ch = curl_init('https://podberimuzyku.ru/billing/confirm-payment.php');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);

        curl_close($ch);

        return back();
    }
}