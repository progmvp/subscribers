<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\DonateController;

/*
|--------------------------------------------------------------------------
| WEB ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    abort(403);
});

/*
|--------------------------------------------------------------------------
| PAY PAGE
|--------------------------------------------------------------------------
*/

Route::get('/pay', function () {

    $name = request('name');
    $email = request('email');
    $plan = request('plan');

    if (!$name || !$email || !$plan) {
        abort(404);
    }

    $plans = [
        'start' => ['name' => 'Стартовый', 'price' => 250],
        'base'  => ['name' => 'Базовый', 'price' => 450],
        'full'  => ['name' => 'Полный', 'price' => 750],
    ];

    if (!isset($plans[$plan])) {
        abort(404);
    }

    $currentPlan = $plans[$plan];

    $existingPayment = DB::table('payments')
        ->where('email', $email)
        ->where('plan', $plan)
        ->where('status', 'pending')
        ->first();

    if ($existingPayment) {

        $paymentId = $existingPayment->payment_id;

    } else {

        $paymentId = Str::uuid();

        DB::table('payments')->insert([
            'name' => $name,
            'email' => $email,
            'plan' => $plan,
            'amount' => $currentPlan['price'],
            'status' => 'pending',
            'payment_id' => $paymentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | TELEGRAM NOTIFICATION (ONLY ON NEW INSERT)
        |--------------------------------------------------------------------------
        */

        $token = '8352442876:AAG_eZ543FDhCkZS3qgg69bEcCmB2xuyEPU';
        $chatId = '1274323121';

        $text =
            "🔔 Новая заявка\n\n" .
            "👤 {$name}\n" .
            "📧 {$email}\n\n" .
            "📦 {$currentPlan['name']}\n" .
            "💰 {$currentPlan['price']} ₽\n\n" .
            "🆔 {$paymentId}\n" .
            "📌 Статус: pending";

        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'chat_id' => $chatId,
            'text' => $text
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

/*    return view('pay', [
        'name' => $name,
        'email' => $email,
        'currentPlan' => $currentPlan,
        'paymentId' => $paymentId
    ]);*/

    return response()->json([
        'success'    => true,
        'paymentId'  => $paymentId,
        'name'       => $name,
        'email'      => $email,
        'plan'       => $plan,
        'planName'   => $currentPlan['name'],
        'amount'     => $currentPlan['price'],
    ]);

});

/*
|--------------------------------------------------------------------------
| DONATE
|--------------------------------------------------------------------------
*/

Route::get('/donate/select', [DonateController::class, 'select']);

/*
|--------------------------------------------------------------------------
| ADMIN LOGIN
|--------------------------------------------------------------------------
*/

Route::get('/admin/login', function () {
    return view('admin.login');
});

Route::post('/admin/login', function () {

    $login = request('login');
    $password = request('password');

    if ($login === 'progmvp0426' && $password === 'kFnxlZ0@j') {
        Session::put('admin', true);
        return redirect('/admin/payments');
    }

    return redirect('/admin/login');
});

Route::get('/admin/logout', function () {
    Session::forget('admin');
    return redirect('/admin/login');
});

Route::get('/admin/payments', function () {

    if (!Session::get('admin')) {
        return redirect('/admin/login');
    }

    $payments = DB::table('payments')
        ->orderBy('created_at', 'desc')
        ->get();

    return view('admin.payments', [
        'payments' => $payments
    ]);
});

Route::get('/admin/payments/confirm', function () {

    if (!Session::get('admin')) {
        return redirect('/admin/login');
    }

    $id = request('id');

    $payment = DB::table('payments')
        ->where('id', $id)
        ->first();

    if (!$payment) {
        abort(404);
    }

    DB::table('payments')
        ->where('id', $id)
        ->update([
            'status' => 'success',
            'updated_at' => now()
        ]);

    $data = [
        'email' => $payment->email,
        'plan' => $payment->plan,
        'status' => 'confirmed'
    ];

    $ch = curl_init('https://podberimuzyku.ru/billing/confirm-payment.php');

    curl_setopt($ch, CURLOPT_USERAGENT, 'EBPPPM-Billing-Internal/1.0');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-MBT-SECRET: MBT_SECRET_2026'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    curl_exec($ch);
    curl_close($ch);

    return redirect('/admin/payments');
});

/*
|--------------------------------------------------------------------------
| DELETE FUNCTION
|--------------------------------------------------------------------------
*/

Route::get('/admin/payments/delete', function () {

    if (!Session::get('admin')) {
        return redirect('/admin/login');
    }

    $id = request('id');

    $payment = DB::table('payments')
        ->where('id', $id)
        ->first();

    if (!$payment) {
        abort(404);
    }

    // 🔄 Удаляем запись на PODBERIMUZYKU.RU

    $data = [
        'email' => $payment->email,
        'plan' => $payment->plan,
        'status' => 'deleted'
    ];

    $ch = curl_init('https://podberimuzyku.ru/billing/confirm-payment.php');

    curl_setopt($ch, CURLOPT_USERAGENT, 'EBPPPM-Billing-Internal/1.0');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-MBT-SECRET: MBT_SECRET_2026'
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    curl_exec($ch);

    curl_close($ch);

    // 🗑️ Удаляем запись на EBPPPM.RU

    DB::table('payments')
        ->where('id', $id)
        ->delete();

    return redirect('/admin/payments');
});