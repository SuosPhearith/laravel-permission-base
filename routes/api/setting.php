<?php

use App\Http\Controllers\Setting\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('role',             [SettingController::class, 'listRole']);
Route::get('role/permission',             [SettingController::class, 'getRoleWithPermission']);
Route::get('role/{role}',             [SettingController::class, 'getRoleById']);
Route::post('role',            [SettingController::class, 'createRole']);
Route::put('role/{role}/toggle-status',            [SettingController::class, 'toggleRole']);
Route::put('role/{role}',            [SettingController::class, 'updateRole']);
Route::delete('role/{role}',          [SettingController::class, 'deleteRole']);

Route::get('permission',          [SettingController::class, 'listPermission']);
Route::put('permission/{permission}',          [SettingController::class, 'togglePermission']);
Route::get('module',          [SettingController::class, 'listModule']);
Route::put('module/{module}',          [SettingController::class, 'toggleModule']);

//::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: SETUP

Route::get('setup',             [SettingController::class, 'setup']);
