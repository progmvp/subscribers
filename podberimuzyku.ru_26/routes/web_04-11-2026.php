<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubscriptionPaymentController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\YooMoneyNotificationController;
use App\Http\Controllers\PmrSsoController;

Route::get('/', function () {
    return redirect()->route('lk.login');
});

/*
|--------------------------------------------------------------------------
| YooMoney
|--------------------------------------------------------------------------
*/

Route::post(
    '/yoomoney/notification',
    [YooMoneyNotificationController::class, 'handle']
);

/*
|--------------------------------------------------------------------------
| Авторизация в личном кабинете
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

/*
|--------------------------------------------------------------------------
| Авторизованные маршруты
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])
        ->name('lk.dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('lk.logout');

    Route::post(
        '/subscription/payment',
        [SubscriptionPaymentController::class, 'create']
    )->name('subscription.payment');

    Route::post(
        '/sso/wordpress',
        [PmrSsoController::class, 'create']
    )->name('pmr.sso.create');
});

/*
|--------------------------------------------------------------------------
| Публичное оформление подписки
|--------------------------------------------------------------------------
*/

Route::get('/subscribe', [SubscriptionController::class, 'show'])
    ->name('subscription.subscribe');

Route::post('/subscribe', [SubscriptionController::class, 'store'])
    ->name('subscription.subscribe.store');

Route::get('/member-subscribers', function () {
    $returnUrl = request()->query('return_url');

    if (is_string($returnUrl)) {
        $parts = parse_url($returnUrl);

        $validReturnUrl = is_array($parts)
            && strtolower($parts['scheme'] ?? '') === 'https'
            && in_array(
                strtolower($parts['host'] ?? ''),
                ['podberimuzyku.ru', 'www.podberimuzyku.ru'],
                true
            )
            && !isset($parts['user'])
            && !isset($parts['pass']);

        if ($validReturnUrl) {
            session(['pmr_sso_return_url' => $returnUrl]);
        }
    }

    $plans = DB::table('plans')
        ->whereIn('id', [1, 2, 3, 4])
        ->orderBy('id')
        ->get();

    return view('member-subscribers', [
        'plans' => $plans,
    ]);
})->name('member-subscribers');
