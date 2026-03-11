<?php

declare(strict_types=1);

use App\Adapters\In\Http\Controllers\HealthCheckController;
use App\Adapters\In\Http\Controllers\IdentityAccess\DisableAdminTransactionCapabilityController;
use App\Adapters\In\Http\Controllers\IdentityAccess\EnableAdminTransactionCapabilityController;
use App\Adapters\In\Http\Controllers\ProductCatalog\CreateProductController;
use App\Adapters\In\Http\Controllers\ProductCatalog\UpdateProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', HealthCheckController::class);

Route::post(
    '/identity-access/admin-transaction-capability/enable',
    EnableAdminTransactionCapabilityController::class,
);

Route::post(
    '/identity-access/admin-transaction-capability/disable',
    DisableAdminTransactionCapabilityController::class,
);

Route::post(
    '/product-catalog/products/create',
    CreateProductController::class,
);

Route::post(
    '/product-catalog/products/{productId}/update',
    UpdateProductController::class,
);
