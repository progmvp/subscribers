<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubscriptionPaymentController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\YooMoneyNotificationController;

Route::get('/', function () {
    return redirect()->route('lk.login');
});

Route::post(
    '/yoomoney/notification',
    [YooMoneyNotificationController::class, 'handle']
);

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

    Route::post(
        '/subscription/payment',
        [SubscriptionPaymentController::class, 'create']
    )->name('subscription.payment');
});

Route::get('/subscribe', [SubscriptionController::class, 'show'])
    ->name('subscription.subscribe');

Route::post('/subscribe', [SubscriptionController::class, 'store'])
    ->name('subscription.subscribe.store');

Route::get('/member-subscribers', function () {
    $plans = DB::table('plans')
        ->whereIn('id', [1, 2, 3, 4])
        ->orderBy('id')
        ->get();

    return view('member-subscribers', [
        'plans' => $plans,
    ]);
})->name('member-subscribers');
