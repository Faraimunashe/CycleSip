<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\StoreProduct;
use App\Services\Mobile\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private readonly CartService $cartService,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        return $this->ok($this->cartService->payload($request->user()));
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_product_id' => ['required', 'integer', 'exists:store_products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $this->cartService->addItem(
            user: $request->user(),
            storeProductId: (int) $validated['store_product_id'],
            quantity: (int) $validated['quantity'],
        );

        return $this->ok($this->cartService->payload($request->user()), 'Item added to cart.');
    }

    public function updateItem(Request $request, StoreProduct $storeProduct): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $this->cartService->updateItem(
            user: $request->user(),
            storeProduct: $storeProduct,
            quantity: (int) $validated['quantity'],
        );

        return $this->ok($this->cartService->payload($request->user()), 'Cart updated.');
    }

    public function removeItem(Request $request, StoreProduct $storeProduct): JsonResponse
    {
        $this->cartService->removeItem($request->user(), $storeProduct);

        return $this->ok($this->cartService->payload($request->user()), 'Item removed from cart.');
    }

    public function clear(Request $request): JsonResponse
    {
        $this->cartService->clear($request->user());

        return $this->ok($this->cartService->payload($request->user()), 'Cart cleared.');
    }
}
