<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'currentYear' => date('Y'),
        'appName' => config('app.name'),
    ]);
})->name('home');

Route::get('/orderhistory', function () {
    return Inertia::render('OrderHistory');
})->middleware(['auth', 'verified'])->name('orderhistory');

Route::get('/neworder', function () {
    return Inertia::render('NewOrder');
})->middleware(['auth', 'verified'])->name('neworder');

Route::get('/exchangerates', function () {
    return Inertia::render('ExchangeRates');
})->middleware(['auth', 'verified'])->name('exchangerates');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
