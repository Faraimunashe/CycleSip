<?php

namespace App\Services\Mobile;

use App\Models\Store;
use App\Support\MediaUrl;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
            ->map(fn (Store $store): array => $this->mapStore($store))
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $stores
     * @return array{categories: list<string>, stores: list<array{id: int, name: string}>}
     */
    public function filterOptions(Collection $stores): array
    {
        $categories = $stores
            ->flatMap(fn (array $store): Collection => collect($store['inventory']))
            ->map(fn (array $item): string => $item['category'] ?? 'General')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $storeOptions = $stores
            ->map(fn (array $store): array => [
                'id' => $store['id'],
                'name' => $store['name'],
            ])
            ->values()
            ->all();

        return [
            'categories' => $categories,
            'stores' => $storeOptions,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $stores
     * @return Collection<int, array<string, mixed>>
     */
    public function filterStores(
        Collection $stores,
        ?string $search = null,
        ?string $category = null,
        ?int $storeId = null,
    ): Collection {
        $query = Str::of($search ?? '')->trim()->lower()->toString();
        $categoryFilter = Str::of($category ?? '')->trim()->toString();

        return $stores
            ->when($storeId !== null, fn (Collection $collection): Collection => $collection->where('id', $storeId))
            ->map(function (array $store) use ($query, $categoryFilter): array {
                $inventory = collect($store['inventory'])->filter(function (array $item) use ($query, $categoryFilter): bool {
                    $matchesSearch = $query === ''
                        || Str::contains(Str::lower($item['product_name']), $query)
                        || Str::contains(Str::lower($item['brand'] ?? ''), $query)
                        || Str::contains(Str::lower($item['description'] ?? ''), $query);

                    $itemCategory = $item['category'] ?? 'General';
                    $matchesCategory = $categoryFilter === '' || $itemCategory === $categoryFilter;

                    return $matchesSearch && $matchesCategory;
                })->values()->all();

                return [
                    ...$store,
                    'inventory' => $inventory,
                ];
            })
            ->filter(fn (array $store): bool => count($store['inventory']) > 0)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapStore(Store $store): array
    {
        return [
            'id' => $store->id,
            'name' => $store->name,
            'logo_url' => MediaUrl::resolve($store->logo_url),
            'address' => $store->address,
            'inventory' => $store->inventory->map(fn ($storeProduct): array => [
                'id' => $storeProduct->id,
                'product_id' => $storeProduct->product_id,
                'store_id' => $store->id,
                'product_name' => $storeProduct->product->name,
                'image_url' => MediaUrl::resolve($storeProduct->product->image_url),
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
        ];
    }
}
