<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Mobile\CartService;
use App\Services\Mobile\CatalogService;
use App\Services\Mobile\DeliveryAddressService;
use App\Services\DeliveryZoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private readonly CatalogService $catalogService,
        private readonly CartService $cartService,
        private readonly DeliveryAddressService $deliveryAddressService,
        private readonly DeliveryZoneService $deliveryZoneService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:80'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
        ]);

        $selectedAddress = $this->deliveryAddressService->selected($request->user());
        $latitude = $selectedAddress?->latitude;
        $longitude = $selectedAddress?->longitude;

        $stores = $this->catalogService->storesWithInventory($latitude, $longitude);
        $filtered = $this->catalogService->filterStores(
            stores: $stores,
            search: $validated['search'] ?? null,
            category: $validated['category'] ?? null,
            storeId: isset($validated['store_id']) ? (int) $validated['store_id'] : null,
        );

        $cart = $this->cartService->payload($request->user());
        $coverage = ($latitude !== null && $longitude !== null)
            ? $this->deliveryZoneService->coverageForPoint((float) $latitude, (float) $longitude)
            : null;

        return $this->ok([
            'stores' => $filtered->values()->all(),
            'filters' => $this->catalogService->filterOptions($stores),
            'cart_summary' => [
                'item_count' => $cart['item_count'],
                'line_count' => $cart['line_count'],
            ],
            'delivery_coverage' => $coverage,
        ]);
    }
}
