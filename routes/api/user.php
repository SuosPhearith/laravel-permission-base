<?php

use App\Http\Controllers\Auth\UserController;
use Illuminate\Support\Facades\Route;

//::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: USER MANAGEMENT ROUTES

// 🔒 view-users
Route::get('/',                                         [UserController::class, 'index'])->middleware('can:view-users');

// 🔒 view-users
Route::get('/{user}',                                   [UserController::class, 'getUserById'])->middleware('can:view-users');

// 🔒 view-session-users
Route::get('/{user}/get-user-permission',               [UserController::class, 'getUserPermission'])->middleware('can:view-session-users');

// 🔒 create-users
Route::post('/',                                        [UserController::class, 'createUser'])->middleware('can:create-users');

// 🔒 edit-users
Route::put('/{user}',                                   [UserController::class, 'editUser'])->middleware('can:edit-users');

// 🔒 delete-users
Route::delete('/{user}',                                [UserController::class, 'deleteUser'])->middleware('can:delete-users');

// 🔒 logout-users
Route::delete('/{user}/logout',                         [UserController::class, 'logoutUser'])->middleware('can:logout-users');

// 🔒 reset-password-users
Route::put('/{user}/reset-password',                    [UserController::class, 'resetPassword'])->middleware('can:reset-password-users');

// 🔒 ban-users
Route::put('/{user}/toggle-status',                     [UserController::class, 'toggleStatus'])->middleware('can:ban-users');

// 🔒 grant-permission-users
Route::post('/{user}/{permission}/add-permission',      [UserController::class, 'addNewPermission'])->middleware('can:grant-permission-users');

// 🔒 update-permission-users
Route::put('/{user}/update-permission',                 [UserController::class, 'updateUserPermission'])->middleware('can:update-permission-users');

// 🔒 enable-2fa-users
Route::put('/{user}/enable-2fa',                        [UserController::class, 'enable2FA'])->middleware('can:enable-2fa-users');

// 🔒 disable-2fa-users
Route::put('/{user}/disable-2fa',                       [UserController::class, 'disable2FA'])->middleware('can:disable-2fa-users');
