<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\DonateController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubscriptionPaymentController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\YooMoneyNotificationController;

/*
|--------------------------------------------------------------------------
| Главная страница
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('lk.login');
});

/*
|--------------------------------------------------------------------------
| YooMoney — новая система подписок
|--------------------------------------------------------------------------
|
| Уведомление об оплате обрабатывается через:
| subscr_checkout -> subscr_payments -> subscriptions.
|
| Старый обработчик таблицы payments удалён из контроллера.
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
|
| Отдельный маршрут сохраняется.
|
*/

Route::get('/donate/select', [DonateController::class, 'select']);

/*
|--------------------------------------------------------------------------
| Личный кабинет
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
| Продление подписки из личного кабинета
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::post(
        '/subscription/payment',
        [SubscriptionPaymentController::class, 'create']
    )->name('subscription.payment');
});

/*
|--------------------------------------------------------------------------
| Первичная покупка подписки
|--------------------------------------------------------------------------
*/

Route::get('/subscribe', [SubscriptionController::class, 'show'])
    ->name('subscription.subscribe');

Route::post('/subscribe', [SubscriptionController::class, 'store'])
    ->name('subscription.subscribe.store');

/*
|--------------------------------------------------------------------------
| Страница выбора тарифа
|--------------------------------------------------------------------------
*/

Route::get('/member-subscribers', function () {
    $plans = DB::table('plans')
        ->whereIn('id', [1, 2, 3, 4])
        ->orderBy('id')
        ->get();

    return view('member-subscribers', [
        'plans' => $plans,
    ]);
})->name('member-subscribers');
