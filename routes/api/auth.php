<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('me',                        [AuthController::class,     'me']);
Route::post('logout',                   [AuthController::class,     'logout']);
Route::delete('logout/{user}',          [AuthController::class,     'logoutUser']);

