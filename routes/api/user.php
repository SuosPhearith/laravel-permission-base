<?php

use App\Http\Controllers\Auth\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/',             [UserController::class, 'index'])->middleware('can:view-users');
Route::post('/',            [UserController::class, 'createUser'])->middleware('can:create-users');
Route::put('/{user}',       [UserController::class, 'editUser'])->middleware('can:edit-users');
