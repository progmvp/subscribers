<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PmrSsoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Старый маршрут /payment-page удалён.
| Новые маршруты подписок находятся в routes/web.php.
|
*/

Route::post(
    '/sso/wordpress/validate',
    [PmrSsoController::class, 'validate']
)->name('pmr.sso.validate');
