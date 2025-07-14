<?php

use App\Http\Controllers\Auth\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/',             [UserController::class, 'index'])->middleware('can:view-users');
Route::get('/{user}',       [UserController::class, 'getUserById'])->middleware('can:view-users');
Route::post('/',            [UserController::class, 'createUser'])->middleware('can:create-users');
Route::put('/{user}',       [UserController::class, 'editUser'])->middleware('can:edit-users');
Route::delete('/{user}',       [UserController::class, 'deleteUser']);
Route::put('/{user}/reset-password',       [UserController::class, 'resetPassword']);
Route::put('/{user}/toggle-status',       [UserController::class, 'toggleStatus']);
