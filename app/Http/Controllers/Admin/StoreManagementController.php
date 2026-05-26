<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUpsertRequest;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StoreManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $stores = Store::query()
            ->with('zones')
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Store $store): array => $this->toStoreArray($store));

        return Inertia::render('Admin/Stores/Index', [
            'stores' => $stores,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Stores/Create', [
            'store' => $this->defaultStorePayload(),
            'availableZones' => $this->deliveryZoneOptions(),
        ]);
    }

    public function store(StoreUpsertRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $store = Store::create($this->buildStorePayload($request) + [
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        $store->zones()->sync($validated['zone_ids'] ?? []);

        return to_route('admin.stores.show', $store)->with('success', 'Store created successfully.');
    }

    public function show(Store $store): Response
    {
        $store->load(['zones', 'inventory.product.category']);

        return Inertia::render('Admin/Stores/Show', [
            'store' => $this->toStoreArray($store, true),
            'availableProducts' => $this->availableProductOptions($store),
        ]);
    }

    public function edit(Store $store): Response
    {
        $store->load('zones');

        return Inertia::render('Admin/Stores/Edit', [
            'store' => $this->toStoreArray($store),
            'availableZones' => $this->deliveryZoneOptions(),
        ]);
    }

    public function update(StoreUpsertRequest $request, Store $store): RedirectResponse
    {
        $validated = $request->validated();

        $store->update($this->buildStorePayload($request, $store));
        $store->zones()->sync($validated['zone_ids'] ?? []);

        return to_route('admin.stores.show', $store)->with('success', 'Store updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function toStoreArray(Store $store, bool $withInventory = false): array
    {
        $payload = [
            'id' => $store->id,
            'name' => $store->name,
            'logo_url' => $store->logo_url,
            'slug' => $store->slug,
            'address' => $store->address,
            'phone' => $store->phone,
            'opening_time' => $store->opening_time,
            'closing_time' => $store->closing_time,
            'commission_rate' => $store->commission_rate,
            'is_active' => $store->is_active,
            'zones' => $store->relationLoaded('zones')
                ? $store->zones->map(fn ($zone): array => ['id' => $zone->id, 'name' => $zone->name])->values()
                : [],
            'zone_ids' => $store->relationLoaded('zones')
                ? $store->zones->pluck('id')->values()->all()
                : [],
            'created_at' => optional($store->created_at)?->toIso8601String(),
            'updated_at' => optional($store->updated_at)?->toIso8601String(),
        ];

        if ($withInventory && $store->relationLoaded('inventory')) {
            $payload['inventory'] = $store->inventory->map(fn ($item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'product_brand' => $item->product?->brand,
                'category_name' => $item->product?->category?->name,
                'stock_quantity' => $item->stock_quantity,
                'price' => (float) $item->price,
                'promotion_price' => $item->promotion_price !== null ? (float) $item->promotion_price : null,
                'promotion_ends_at' => optional($item->promotion_ends_at)?->format('Y-m-d\TH:i'),
                'is_available' => (bool) $item->is_available,
            ])->values();
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultStorePayload(): array
    {
        return [
            'name' => '',
            'logo_url' => '',
            'remove_logo' => false,
            'slug' => '',
            'address' => '',
            'phone' => '',
            'opening_time' => '09:00',
            'closing_time' => '22:00',
            'commission_rate' => 15,
            'is_active' => true,
            'zone_ids' => [],
        ];
    }

    /**
     * @return list<array{id: int, name: string, is_active: bool}>
     */
    private function deliveryZoneOptions(): array
    {
        return DeliveryZone::query()
            ->orderBy('name')
            ->get(['id', 'name', 'is_active'])
            ->map(fn (DeliveryZone $zone): array => [
                'id' => $zone->id,
                'name' => $zone->name,
                'is_active' => (bool) $zone->is_active,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, brand: string|null, category_name: string|null}>
     */
    private function availableProductOptions(Store $store): array
    {
        $assignedProductIds = $store->inventory()->pluck('product_id');

        return Product::query()
            ->with('category:id,name')
            ->where('is_active', true)
            ->when($assignedProductIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $assignedProductIds))
            ->orderBy('name')
            ->get(['id', 'name', 'brand', 'product_category_id'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand,
                'category_name' => $product->category?->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStorePayload(StoreUpsertRequest $request, ?Store $store = null): array
    {
        $payload = $request->safe()->except(['logo', 'remove_logo']);

        if ($store && $request->boolean('remove_logo')) {
            $this->deleteStoredLogo($store->logo_url);
            $payload['logo_url'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($store) {
                $this->deleteStoredLogo($store->logo_url);
            }

            $payload['logo_url'] = Storage::url($request->file('logo')->store('stores', 'public'));
        }

        return $payload;
    }

    private function deleteStoredLogo(?string $logoUrl): void
    {
        if (! $logoUrl || ! str_starts_with($logoUrl, '/storage/')) {
            return;
        }

        $path = ltrim(str_replace('/storage/', '', $logoUrl), '/');

        if ($path !== '') {
            Storage::disk('public')->delete($path);
        }
    }
}
