<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\ProductCatalog\DatabaseProductDetailReaderAdapter;
use App\Adapters\Out\ProductCatalog\DatabaseProductDuplicateCheckerAdapter;
use App\Adapters\Out\ProductCatalog\DatabaseProductReaderAdapter;
use App\Adapters\Out\ProductCatalog\DatabaseProductTableReaderAdapter;
use App\Adapters\Out\ProductCatalog\DatabaseVersionedProductWriterAdapter;
use App\Application\ProductCatalog\Context\ProductChangeContext;
use App\Core\ProductCatalog\Policies\MinSellingPricePolicy;
use App\Ports\Out\ProductCatalog\ProductDetailReaderPort;
use App\Ports\Out\ProductCatalog\ProductDuplicateCheckerPort;
use App\Ports\Out\ProductCatalog\ProductLifecyclePort;
use App\Ports\Out\ProductCatalog\ProductReaderPort;
use App\Ports\Out\ProductCatalog\ProductTableReaderPort;
use App\Ports\Out\ProductCatalog\ProductWriterPort;
use Illuminate\Support\ServiceProvider;

class ProductCatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ProductChangeContext::class, fn (): ProductChangeContext => new ProductChangeContext());

        $this->app->singleton(MinSellingPricePolicy::class);

        $this->app->singleton(ProductReaderPort::class, DatabaseProductReaderAdapter::class);
        $this->app->singleton(ProductDetailReaderPort::class, DatabaseProductDetailReaderAdapter::class);
        $this->app->singleton(ProductTableReaderPort::class, DatabaseProductTableReaderAdapter::class);
        $this->app->scoped(ProductWriterPort::class, DatabaseVersionedProductWriterAdapter::class);
        $this->app->scoped(ProductLifecyclePort::class, DatabaseVersionedProductWriterAdapter::class);
        $this->app->singleton(ProductDuplicateCheckerPort::class, DatabaseProductDuplicateCheckerAdapter::class);
    }
}
