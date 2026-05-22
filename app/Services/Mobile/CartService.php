<?php

namespace App\Services\Mobile;

use App\Models\CartItem;
use App\Support\MediaUrl;
use App\Models\StoreProduct;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function addItem(User $user, int $storeProductId, int $quantity): void
    {
        $storeProduct = StoreProduct::query()->findOrFail($storeProductId);

        if (! $storeProduct->is_available || $storeProduct->stock_quantity < 1) {
            throw ValidationException::withMessages([
                'store_product_id' => 'This product is currently unavailable.',
            ]);
        }

        $cartItem = CartItem::query()->firstOrNew([
            'user_id' => $user->id,
            'store_product_id' => $storeProduct->id,
        ]);

        $existingQuantity = (int) ($cartItem->quantity ?? 0);
        $nextQuantity = min($existingQuantity + $quantity, max(1, (int) $storeProduct->stock_quantity));

        $cartItem->quantity = $nextQuantity;
        $cartItem->save();
    }

    public function updateItem(User $user, StoreProduct $storeProduct, int $quantity): void
    {
        if ($quantity === 0) {
            $this->removeItem($user, $storeProduct);

            return;
        }

        $cartItem = CartItem::query()
            ->where('user_id', $user->id)
            ->where('store_product_id', $storeProduct->id)
            ->first();

        if (! $cartItem) {
            throw ValidationException::withMessages([
                'quantity' => 'This item is not in your cart.',
            ]);
        }

        $cartItem->update([
            'quantity' => min($quantity, max(1, (int) $storeProduct->stock_quantity)),
        ]);
    }

    public function removeItem(User $user, StoreProduct $storeProduct): void
    {
        CartItem::query()
            ->where('user_id', $user->id)
            ->where('store_product_id', $storeProduct->id)
            ->delete();
    }

    public function clear(User $user): void
    {
        CartItem::query()->where('user_id', $user->id)->delete();
    }

    /**
     * @return Collection<int, array{store_product: StoreProduct, quantity: int, unit_price: float}>
     */
    public function lines(User $user): Collection
    {
        $cartItems = CartItem::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('store_product_id');

        if ($cartItems->isEmpty()) {
            return collect();
        }

        $storeProducts = StoreProduct::query()
            ->with(['store', 'product'])
            ->whereIn('id', $cartItems->keys())
            ->get()
            ->keyBy('id');

        return $cartItems
            ->map(function (CartItem $cartItem) use ($storeProducts): ?array {
                $storeProduct = $storeProducts->get($cartItem->store_product_id);

                if (! $storeProduct) {
                    return null;
                }

                return [
                    'store_product' => $storeProduct,
                    'quantity' => (int) $cartItem->quantity,
                    'unit_price' => $this->unitPrice($storeProduct),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(User $user): array
    {
        $lines = $this->lines($user);

        $stores = $lines
            ->groupBy(fn (array $line): int => (int) $line['store_product']->store_id)
            ->map(function (Collection $storeLines, int $storeId): array {
                $firstStoreProduct = $storeLines->first()['store_product'];

                return [
                    'id' => $storeId,
                    'name' => $firstStoreProduct->store?->name,
                    'address' => $firstStoreProduct->store?->address,
                    'items' => $storeLines->map(fn (array $line): array => [
                        'store_product_id' => $line['store_product']->id,
                        'product_name' => $line['store_product']->product?->name,
                        'image_url' => MediaUrl::resolve($line['store_product']->product?->image_url),
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'line_total' => round($line['quantity'] * $line['unit_price'], 2),
                        'stock_quantity' => (int) $line['store_product']->stock_quantity,
                        'is_available' => (bool) $line['store_product']->is_available,
                    ])->values(),
                ];
            })
            ->values();

        return [
            'line_count' => $lines->count(),
            'item_count' => $lines->sum('quantity'),
            'subtotal' => round($lines->sum(fn (array $line): float => $line['quantity'] * $line['unit_price']), 2),
            'delivery_fee_per_store' => 2.50,
            'stores' => $stores,
        ];
    }

    /**
     * @param  Collection<int, array{store_product: StoreProduct, quantity: int, unit_price: float}>  $lines
     */
    public function removeLines(User $user, Collection $lines): void
    {
        $storeProductIds = $lines
            ->map(fn (array $line): int => (int) $line['store_product']->id)
            ->all();

        CartItem::query()
            ->where('user_id', $user->id)
            ->whereIn('store_product_id', $storeProductIds)
            ->delete();
    }

    private function unitPrice(StoreProduct $storeProduct): float
    {
        return $storeProduct->promotion_price !== null
            ? (float) $storeProduct->promotion_price
            : (float) $storeProduct->price;
    }
}
