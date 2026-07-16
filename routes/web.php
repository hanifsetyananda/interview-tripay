<?php

use Illuminate\Support\Facades\Route;

// Route::inertia('/', 'Welcome')->name('home');
// Route::inertia('/', 'Dashboard')->name('home');
Route::get('/', [DashboardController::class, 'index'])->name('home');