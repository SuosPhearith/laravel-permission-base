<?php

use App\Http\Controllers\Billing\ExchangeController;
use Illuminate\Support\Facades\Route;

Route::get('/',     [ExchangeController::class, 'index']);
Route::get('/doc',     [ExchangeController::class, 'getExchangeRateDoc']);
Route::post('/',    [ExchangeController::class, 'create']);
Route::put('/{exchangeRate}',    [ExchangeController::class, 'makeActive']);