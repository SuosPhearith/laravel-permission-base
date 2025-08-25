<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Setting\SettingController;
use Illuminate\Support\Facades\Route;

// :::::::::::::::::::::::::::::::::::::::::::::::::::::: RATE LIMIT 120 TIMES PER MINUTE
Route::middleware('throttle:120,1')->group(function () {

    Route::get('/', function () {
        return 'Pharmacy API is running';
    });

    //:::::::::::::::::::::::::::::::::::::::::::::::: PHARMACY

    Route::prefix('v1')->group(function () {

        Route::post('auth/login',       [AuthController::class, 'login'])->middleware('throttle:50,1');
        Route::post('auth/register',    [AuthController::class, 'register'])->middleware('throttle:50,1');

        Route::middleware(['authentication.jwt.auth'])->group(function () {
            Route::prefix('pharmacy')->group(function () {
                require_once __DIR__ . '/api/pharmacy.php';
            });
            Route::prefix('auth')->group(function () {
                require_once __DIR__ . '/api/auth.php';
            });
            Route::prefix('user')->group(function () {
                require_once __DIR__ . '/api/user.php';
            });
            Route::prefix('setting')->group(function () {
                require_once __DIR__ . '/api/setting.php';
            });
            Route::prefix('config')->group(function () {
                require_once __DIR__ . '/api/config.php';
            });
        });
    });

    //:::::::::::::::::::::::::::::::::::::::::::::::: PHARMACY

});
