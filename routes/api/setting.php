<?php

use App\Http\Controllers\Setting\SettingController;
use Illuminate\Support\Facades\Route;

//::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: ROLE MANAGEMENT

// 🔒 view-role-setting
Route::get('role',                          [SettingController::class, 'listRole'])->middleware('can:view-role-setting');

// 🔒 view-role-setting
Route::get('role/permission',               [SettingController::class, 'getRoleWithPermission'])->middleware('can:view-role-setting');

// 🔒 view-role-setting
Route::get('role/{role}',                   [SettingController::class, 'getRoleById'])->middleware('can:view-role-setting');

// 🔒 create-role-setting
Route::post('role',                         [SettingController::class, 'createRole'])->middleware('can:create-role-setting');

// 🔒 toggle-role-setting
Route::put('role/{role}/toggle-status',     [SettingController::class, 'toggleRole'])->middleware('can:toggle-role-setting');

// 🔒 update-role-setting
Route::put('role/{role}',                   [SettingController::class, 'updateRole'])->middleware('can:update-role-setting');

// 🔒 delete-role-setting
Route::delete('role/{role}',                [SettingController::class, 'deleteRole'])->middleware('can:delete-role-setting');

//::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: PERMISSION MANAGEMENT

// 🔒 view-permission-setting
Route::get('permission',                    [SettingController::class, 'listPermission'])->middleware('can:view-permission-setting');

// 🔒 toggle-permission-setting
Route::put('permission/{permission}',       [SettingController::class, 'togglePermission'])->middleware('can:toggle-permission-setting');

// 🔒 toggle-permission-setting
Route::delete('permission/{permission}',    [SettingController::class, 'deletePermission'])->middleware('can:delete-permission-setting'); // not

// 🔒 create-permission-setting
Route::post('permission/{module}/create',   [SettingController::class, 'createPermission'])->middleware('can:create-permission-setting');

//::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: MODULE MANAGEMENT

// 🔒 view-module-setting
Route::get('module',                        [SettingController::class, 'listModule'])->middleware('can:view-module-setting');

// 🔒 create-module-setting
Route::post('module',                       [SettingController::class, 'createModule'])->middleware('can:create-module-setting');

// 🔒 toggle-module-setting
Route::put('module/{module}',               [SettingController::class, 'toggleModule'])->middleware('can:toggle-module-setting');

// 🔒 toggle-module-setting
Route::delete('module/{module}',            [SettingController::class, 'deleteModule'])->middleware('can:delete-module-setting'); // not

//::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: SETUP

// 🔒 view-setting
Route::get('setup',                         [SettingController::class, 'setup'])->middleware('can:view-setting');
