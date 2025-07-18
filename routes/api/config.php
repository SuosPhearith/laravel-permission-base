<?php

use App\Http\Controllers\Setting\ConfigController;
use Illuminate\Support\Facades\Route;

//::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: CONFIG MANAGEMENT

Route::get('/',                          [ConfigController::class, 'globalSetting']);
Route::put('/app-config',                [ConfigController::class, 'updateApp'])->middleware('can:view-config-setting');
Route::put('/app-datetime',              [ConfigController::class, 'updateDatetimeFormat'])->middleware('can:view-config-setting');