<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Mobile\CartService;
use App\Services\Mobile\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private readonly CatalogService $catalogService,
        private readonly CartService $cartService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->payload($request->user());

        return $this->ok([
            'stores' => $this->catalogService->storesWithInventory(),
            'cart_summary' => [
                'item_count' => $cart['item_count'],
                'line_count' => $cart['line_count'],
            ],
        ]);
    }
}
