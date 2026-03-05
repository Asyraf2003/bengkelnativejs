<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\{ShowLoginController, LoginController, LogoutController};
use App\Http\Controllers\Admin\Products\IndexController as ProductIndex;
use App\Http\Controllers\Admin\Inventory\Adjustments\{CreateController, StoreController};

Route::middleware('guest')->group(function () {
    Route::get('/login', ShowLoginController::class)->name('login.show');
    Route::post('/login', LoginController::class)->name('login.perform');
});

Route::post('/logout', LogoutController::class)
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'admin.only'])
    ->prefix('admin')
    ->name('admin.') // Menambahkan prefix 'admin.' ke semua nama route di dalam grup
    ->group(function () {
        
        Route::view('/', 'admin.dashboard')->name('dashboard');

        // Products
        Route::get('/products', ProductIndex::class)->name('products.index');

        // Inventory Adjustments
        Route::prefix('inventory/adjustments')->name('inventory.adjustments.')->group(function () {
            Route::get('/create', CreateController::class)->name('create');
            Route::post('/', StoreController::class)->name('store');
        });
    });
