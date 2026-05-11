<?php

declare(strict_types=1);

use App\Adapters\In\Http\Controllers\Api\V1\Auth\LoginMobileApiController;
use App\Adapters\In\Http\Controllers\Api\V1\Auth\LogoutMobileApiController;
use App\Adapters\In\Http\Controllers\Api\V1\Auth\MeMobileApiController;
use App\Adapters\In\Http\Controllers\Api\V1\Product\SearchMobileApiProductsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', LoginMobileApiController::class)
        ->name('api.v1.auth.login');

    Route::middleware('mobile.api.auth')->group(function (): void {
        Route::get('/me', MeMobileApiController::class)
            ->name('api.v1.me');

        Route::post('/auth/logout', LogoutMobileApiController::class)
            ->name('api.v1.auth.logout');

        Route::get('/products/search', SearchMobileApiProductsController::class)
            ->name('api.v1.products.search');
    });
});
