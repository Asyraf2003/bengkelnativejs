<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Api\V1\Product;

use App\Application\IdentityAccess\Services\LoginActorAccessDecision;
use App\Application\MobileApi\Auth\DTO\MobileApiActor;
use App\Application\MobileApi\Product\UseCases\SearchMobileApiProductsHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class SearchMobileApiProductsController extends Controller
{
    public function __construct(private readonly SearchMobileApiProductsHandler $products)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $actor = $request->attributes->get('mobile_api_actor');

        if (!$actor instanceof MobileApiActor) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Autentikasi diperlukan.',
                'errors' => [
                    'token' => ['UNAUTHENTICATED'],
                ],
            ], 401);
        }

        if ($actor->role !== LoginActorAccessDecision::KASIR) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Akses produk mobile hanya untuk kasir.',
                'errors' => [
                    'role' => ['CASHIER_ONLY'],
                ],
            ], 403);
        }

        $result = $this->products->handle((string) $request->query('q', ''));

        return response()->json([
            'success' => true,
            'data' => [
                'rows' => $result['rows'],
            ],
            'meta' => $result['meta'],
            'errors' => null,
        ]);
    }
}
