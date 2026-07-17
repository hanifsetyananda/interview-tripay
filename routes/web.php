<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductsController;
use Illuminate\Support\Facades\Route;

// Route::inertia('/', 'Welcome')->name('home');
// Route::inertia('/', 'Dashboard')->name('home');
Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::resource('products', ProductsController::class)->except('show');
Route::get('/cart', [DashboardController::class, 'cart'])->name('cart');
