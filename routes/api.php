<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\OrderController;

// Currencies
Route::get('/currencies', [CurrencyController::class, 'index']);
Route::get('/currencies/list', [CurrencyController::class, 'list']);
Route::get('/currencies/inactive', [CurrencyController::class, 'inactive']);
Route::get('/currencies/source', [CurrencyController::class, 'source']);
Route::get('/currencies/{currencyCode}', [CurrencyController::class, 'show'])->where('currencyCode', '[A-Z]{3}');
Route::post('/currencies/update/{currencyCode}/{field}/{value}', [CurrencyController::class, 'update']);
Route::post('/currencies/updateAll', [CurrencyController::class, 'updateAll']);
Route::post('/currencies/activate/{currencyCode}', [CurrencyController::class, 'activate']);
Route::post('/currencies/deactivate/{currencyCode}', [CurrencyController::class, 'deactivate']);
Route::post('/currencies/enableSendOrderEmail/{currencyCode}', [CurrencyController::class, 'enableSendOrderEmail']);
Route::post('/currencies/disableSendOrderEmail/{currencyCode}', [CurrencyController::class, 'disableSendOrderEmail']);

// Orders
Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/user/{userId}', [OrderController::class, 'showOrdersForUser']);
Route::get('/orders/currency/{currencyId}', [OrderController::class, 'showOrdersForCurrency']);