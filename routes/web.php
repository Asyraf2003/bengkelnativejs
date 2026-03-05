<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ShowLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;

Route::get('/login', ShowLoginController::class)
    ->middleware('guest')
    ->name('login.show');

Route::post('/login', LoginController::class)
    ->middleware('guest')
    ->name('login.perform');

Route::post('/logout', LogoutController::class)
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'admin.only'])
    ->prefix('admin')
    ->group(function () {
        Route::view('/', 'admin.dashboard')->name('admin.dashboard');
    });
