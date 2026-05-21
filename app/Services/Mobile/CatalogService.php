<?php

namespace App\Services\Mobile;

use App\Models\Store;
use Illuminate\Support\Collection;

class CatalogService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function storesWithInventory(): Collection
    {
        return Store::query()
            ->where('is_active', true)
            ->with([
                'inventory' => fn ($query) => $query
                    ->where('stock_quantity', '>', 0)
                    ->where('is_available', true)
                    ->with('product.category')
                    ->orderBy('product_id'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Store $store): array => [
                'id' => $store->id,
                'name' => $store->name,
                'logo_url' => $store->logo_url,
                'address' => $store->address,
                'inventory' => $store->inventory->map(fn ($storeProduct): array => [
                    'id' => $storeProduct->id,
                    'product_id' => $storeProduct->product_id,
                    'product_name' => $storeProduct->product->name,
                    'image_url' => $storeProduct->product->image_url,
                    'category' => $storeProduct->product->category?->name,
                    'brand' => $storeProduct->product->brand,
                    'description' => $storeProduct->product->description,
                    'price' => (float) $storeProduct->price,
                    'promotion_price' => $storeProduct->promotion_price ? (float) $storeProduct->promotion_price : null,
                    'effective_price' => $storeProduct->promotion_price !== null
                        ? (float) $storeProduct->promotion_price
                        : (float) $storeProduct->price,
                    'stock_quantity' => $storeProduct->stock_quantity,
                ])->values(),
            ])
            ->values();
    }
}
