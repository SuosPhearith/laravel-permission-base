<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('me',                        [AuthController::class, 'me'])->name('me');
Route::post('logout',                   [AuthController::class, 'logout']);
Route::delete('logout/{user}',          [AuthController::class, 'logoutUser']);
Route::post('update-profile',           [AuthController::class, 'updateProfile']);

//::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: 2FA

Route::post('/2fa/setup',               [AuthController::class, 'setup2FA']);
Route::post('/2fa/verify',              [AuthController::class, 'verify2FA'])->name('verify_2fa');


