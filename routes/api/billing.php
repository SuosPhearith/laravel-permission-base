<?php

use Illuminate\Support\Facades\Route;

Route::prefix('exchange')->group(function () {
    require_once __DIR__ . '/billing/exchange.php';
});
