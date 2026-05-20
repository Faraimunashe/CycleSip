<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliveryZoneUpsertRequest;
use App\Models\DeliveryZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ZoneManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $zones = DeliveryZone::query()
            ->withCount(['stores', 'riderProfiles', 'orders'])
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (DeliveryZone $zone): array => $this->toZoneArray($zone));

        return Inertia::render('Admin/Zones/Index', [
            'zones' => $zones,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Zones/Create', [
            'zone' => $this->defaultZonePayload(),
        ]);
    }

    public function store(DeliveryZoneUpsertRequest $request): RedirectResponse
    {
        $zone = DeliveryZone::create($request->validated());

        return to_route('admin.zones.show', $zone)->with('success', 'Delivery zone created.');
    }

    public function show(DeliveryZone $zone): Response
    {
        $zone->loadCount(['stores', 'riderProfiles', 'orders']);

        return Inertia::render('Admin/Zones/Show', [
            'zone' => $this->toZoneArray($zone),
        ]);
    }

    public function edit(DeliveryZone $zone): Response
    {
        return Inertia::render('Admin/Zones/Edit', [
            'zone' => $this->toZoneArray($zone),
        ]);
    }

    public function update(DeliveryZoneUpsertRequest $request, DeliveryZone $zone): RedirectResponse
    {
        $zone->update($request->validated());

        return to_route('admin.zones.show', $zone)->with('success', 'Delivery zone updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function toZoneArray(DeliveryZone $zone): array
    {
        return [
            'id' => $zone->id,
            'name' => $zone->name,
            'slug' => $zone->slug,
            'center_latitude' => (float) $zone->center_latitude,
            'center_longitude' => (float) $zone->center_longitude,
            'radius_km' => (float) $zone->radius_km,
            'base_delivery_fee' => (float) $zone->base_delivery_fee,
            'distance_surcharge_per_km' => (float) $zone->distance_surcharge_per_km,
            'estimated_minutes' => (int) $zone->estimated_minutes,
            'is_active' => (bool) $zone->is_active,
            'stores_count' => $zone->stores_count ?? 0,
            'rider_profiles_count' => $zone->rider_profiles_count ?? 0,
            'orders_count' => $zone->orders_count ?? 0,
            'created_at' => optional($zone->created_at)?->toIso8601String(),
            'updated_at' => optional($zone->updated_at)?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultZonePayload(): array
    {
        return [
            'name' => '',
            'slug' => '',
            'center_latitude' => -17.8252,
            'center_longitude' => 31.0335,
            'radius_km' => 3,
            'base_delivery_fee' => 2.5,
            'distance_surcharge_per_km' => 0.75,
            'estimated_minutes' => 25,
            'is_active' => true,
        ];
    }
}
