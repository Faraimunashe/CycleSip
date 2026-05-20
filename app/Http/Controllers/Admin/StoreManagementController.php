<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUpsertRequest;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    public function store(StoreUpsertRequest $request): RedirectResponse
    {
        $store = Store::create($request->validated() + [
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        return to_route('admin.stores.show', $store)->with('success', 'Store created successfully.');
    }

    public function show(Store $store): Response
    {
        $store->load(['zones', 'inventory.product']);

        return Inertia::render('Admin/Stores/Show', [
            'store' => $this->toStoreArray($store, true),
        ]);
    }

    public function edit(Store $store): Response
    {
        return Inertia::render('Admin/Stores/Edit', [
            'store' => $this->toStoreArray($store),
        ]);
    }

    public function update(StoreUpsertRequest $request, Store $store): RedirectResponse
    {
        $store->update($request->validated());

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
            'created_at' => optional($store->created_at)?->toIso8601String(),
            'updated_at' => optional($store->updated_at)?->toIso8601String(),
        ];

        if ($withInventory && $store->relationLoaded('inventory')) {
            $payload['inventory'] = $store->inventory->map(fn ($item): array => [
                'id' => $item->id,
                'product_name' => $item->product?->name,
                'stock_quantity' => $item->stock_quantity,
                'price' => (float) $item->price,
                'is_available' => $item->is_available,
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
            'slug' => '',
            'address' => '',
            'phone' => '',
            'opening_time' => '09:00',
            'closing_time' => '22:00',
            'commission_rate' => 15,
            'is_active' => true,
        ];
    }
}
