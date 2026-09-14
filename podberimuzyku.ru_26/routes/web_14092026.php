<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\DonateController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubscriptionPaymentController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\YooMoneyNotificationController;
//use App\Http\Controllers\SubscribeWPApiTokenController;
use App\Services\SubscrSubscriptionService;

/*
|--------------------------------------------------------------------------
| WEB ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('lk.login');
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
        'test'  => ['name' => 'Тестовый', 'price' => 2],
        'start' => ['name' => 'Стартовый', 'price' => 250],
        'base'  => ['name' => 'Базовый', 'price' => 450],
        'full'  => ['name' => 'Полный', 'price' => 750],
    ];

    if (!isset($plans[$plan])) {
        abort(404);
    }

    if ($plan === 'test' && request('test_key') !== env('YOOMONEY_TEST_KEY')) {
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
    }

    /*
    |--------------------------------------------------------------------------
    | YOOMONEY PAYMENT
    |--------------------------------------------------------------------------
    */

    $yoomoneyReceiver = env('YOOMONEY_RECEIVER');

    if (!$yoomoneyReceiver) {
        abort(500, 'YOOMONEY_RECEIVER is not configured');
    }

    return response()->view('payment.yoomoney', [
        'name' => $name,
        'email' => $email,
        'plan' => $plan,
        'planName' => $currentPlan['name'],
        'amount' => $currentPlan['price'],
        'paymentId' => $paymentId,
        'yoomoneyReceiver' => $yoomoneyReceiver,
    ]);
});

/*
|--------------------------------------------------------------------------
| YOOMONEY HTTP NOTIFICATION
|--------------------------------------------------------------------------
|
| Один endpoint используется одновременно для:
|
| 1. новой цепочки subscr_checkout / subscr_payments;
| 2. старой цепочки payments.
|
| Обработка находится в YooMoneyNotificationController.
|
*/

Route::post(
    '/yoomoney/notification',
    [YooMoneyNotificationController::class, 'handle']
);

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

    if (
        $login === env('ADMIN_LOGIN') &&
        $password === env('ADMIN_PASSWORD')
    ) {
        Session::put('admin', true);

        return redirect('/admin/payments');
    }

    return redirect('/admin/login');
});

Route::get('/admin/logout', function () {

    Session::forget('admin');

    return redirect('/admin/login');
});

/*
|--------------------------------------------------------------------------
| ADMIN — WORDPRESS API TOKEN
|--------------------------------------------------------------------------
*/

/*Route::get('/admin/wp-api-token', function () {

    if (!Session::get('admin')) {
        return redirect('/admin/login');
    }

    return app(SubscribeWPApiTokenController::class)->show();
});

Route::post('/admin/wp-api-token', function () {

    if (!Session::get('admin')) {
        return response()->json([
            'success' => false,
            'message' => 'Необходима авторизация администратора.',
        ], 403);
    }

    return app(SubscribeWPApiTokenController::class)->store(request());
});*/

/*
|--------------------------------------------------------------------------
| ADMIN PAYMENTS
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| MANUAL CONFIRM
|--------------------------------------------------------------------------
*/

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

    $ch = curl_init(
        'https://podberimuzyku.ru/billing/confirm-payment.php'
    );

    curl_setopt(
        $ch,
        CURLOPT_USERAGENT,
        'PODBERIMUZYKU-Billing-Internal/1.0'
    );

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        #'X-MBT-SECRET: MBT_SECRET_2026'
        'X-MBT-SECRET: ' . env('MBT_SECRET')
    ]);

    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        json_encode($data)
    );

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

    /*
    |--------------------------------------------------------------------------
    | Удаляем запись на PODBERIMUZYKU.RU
    |--------------------------------------------------------------------------
    */

    $data = [
        'email' => $payment->email,
        'plan' => $payment->plan,
        'status' => 'deleted'
    ];

    $ch = curl_init(
        'https://podberimuzyku.ru/billing/confirm-payment.php'
    );

    curl_setopt(
        $ch,
        CURLOPT_USERAGENT,
        'PODBERIMUZYKU-Billing-Internal/1.0'
    );

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        #'X-MBT-SECRET: MBT_SECRET_2026'
        'X-MBT-SECRET: ' . env('MBT_SECRET')
    ]);

    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        json_encode($data)
    );

    curl_exec($ch);

    curl_close($ch);

    /*
    |--------------------------------------------------------------------------
    | Удаляем запись из payments
    |--------------------------------------------------------------------------
    */

    DB::table('payments')
        ->where('id', $id)
        ->delete();

    return redirect('/admin/payments');
});

/*
|--------------------------------------------------------------------------
| USER PERSONAL CABINET
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('lk.login');

Route::post('/login', [AuthController::class, 'sendCode'])
    ->name('lk.send-code');

Route::get('/verify-code', [AuthController::class, 'showVerify'])
    ->name('lk.verify');

Route::post('/verify-code', [AuthController::class, 'verifyCode'])
    ->name('lk.verify-code');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [AuthController::class, 'dashboard'])
        ->name('lk.dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('lk.logout');
});

/*
|--------------------------------------------------------------------------
| TEST PAY
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::post(
        '/subscription/payment',
        [SubscriptionPaymentController::class, 'create']
    )->name('subscription.payment');
});

/*
|--------------------------------------------------------------------------
| PUBLIC SUBSCRIPTION
|--------------------------------------------------------------------------
*/

Route::get('/subscribe', [SubscriptionController::class, 'show'])
    ->name('subscription.subscribe');

Route::post('/subscribe', [SubscriptionController::class, 'store'])
    ->name('subscription.subscribe.store');
