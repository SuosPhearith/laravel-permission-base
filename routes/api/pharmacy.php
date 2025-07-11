<?php

use App\Http\Controllers\Pharmacy\ProductController;
use Illuminate\Support\Facades\Route;

//:::::::::::::::::::::::::::::::::::::::::::::::::::: SETUP

Route::prefix('product-category')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

Route::prefix('product-type')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

Route::prefix('product-route')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

Route::prefix('product-form')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

Route::prefix('product-origin')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

Route::prefix('expense-source')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

Route::prefix('request-signer')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

//:::::::::::::::::::::::::::::::::::::::::::::::::::: SETUP

//:::::::::::::::::::::::::::::::::::::::::::::::::::: INVENTORY

Route::prefix('product')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

Route::prefix('receive')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

Route::prefix('movement')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

Route::prefix('adjustment')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

Route::prefix('supplier')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

//:::::::::::::::::::::::::::::::::::::::::::::::::::: INVENTORY

//:::::::::::::::::::::::::::::::::::::::::::::::::::: REQUEST

Route::prefix('request')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

Route::prefix('approve')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

Route::prefix('allowcate')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});

//:::::::::::::::::::::::::::::::::::::::::::::::::::: REQUEST