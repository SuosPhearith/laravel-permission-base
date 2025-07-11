<?php

use App\Http\Controllers\Setting\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('role',             [SettingController::class, 'listRole']);
Route::post('role',            [SettingController::class, 'createRole']);
Route::delete('role',          [SettingController::class, 'deleteRole']);

Route::get('permission',          [SettingController::class, 'listPermission']);
Route::get('module',          [SettingController::class, 'listModule']);