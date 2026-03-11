<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\ProductCatalog;

use App\Adapters\In\Http\Presenters\JsonPresenter;
use App\Adapters\In\Http\Requests\ProductCatalog\UpdateProductRequest;
use App\Application\ProductCatalog\UseCases\UpdateProductHandler;
use Illuminate\Http\JsonResponse;

final class UpdateProductController
{
    public function __invoke(
        UpdateProductRequest $request,
        UpdateProductHandler $useCase,
        JsonPresenter $presenter,
        string $productId,
    ): JsonResponse {
        $data = $request->validated();

        $result = $useCase->handle(
            $productId,
            isset($data['kode_barang']) ? (string) $data['kode_barang'] : null,
            (string) $data['nama_barang'],
            (string) $data['merek'],
            isset($data['ukuran']) ? (int) $data['ukuran'] : null,
            (int) $data['harga_jual'],
        );

        if ($result->isFailure()) {
            return $presenter->failure($result);
        }

        return $presenter->success($result);
    }
}
