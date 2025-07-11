<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Pharmacy API is running';
});

//:::::::::::::::::::::::::::::::::::::::::::::::: PHARMACY

Route::prefix('v1')->group(function () {

    Route::post('auth/login', [AuthController::class, 'login']);

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
    });
});

//:::::::::::::::::::::::::::::::::::::::::::::::: PHARMACY