<?php

namespace App\Services;

use App\Models\DeliveryZone;
use App\Models\Store;
use App\Support\Geo;
use Illuminate\Support\Collection;

class DeliveryZoneService
{
    /**
     * @return Collection<int, DeliveryZone>
     */
    public function zonesCoveringPoint(float $latitude, float $longitude): Collection
    {
        return DeliveryZone::query()
            ->where('is_active', true)
            ->get()
            ->filter(function (DeliveryZone $zone) use ($latitude, $longitude): bool {
                return Geo::isWithinRadius(
                    $latitude,
                    $longitude,
                    (float) $zone->center_latitude,
                    (float) $zone->center_longitude,
                    (float) $zone->radius_km,
                );
            })
            ->sortBy(fn (DeliveryZone $zone): float => Geo::distanceKm(
                $latitude,
                $longitude,
                (float) $zone->center_latitude,
                (float) $zone->center_longitude,
            ))
            ->values();
    }

    /**
     * @return Collection<int, Store>
     */
    public function storesServingPoint(float $latitude, float $longitude): Collection
    {
        $zoneIds = $this->zonesCoveringPoint($latitude, $longitude)->pluck('id');

        if ($zoneIds->isEmpty()) {
            return collect();
        }

        return Store::query()
            ->where('is_active', true)
            ->whereHas('zones', fn ($query) => $query->whereIn('delivery_zones.id', $zoneIds))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function coverageForPoint(float $latitude, float $longitude): array
    {
        $zones = $this->zonesCoveringPoint($latitude, $longitude);
        $stores = $this->storesServingPoint($latitude, $longitude);
        $primaryZone = $zones->first();

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'is_serviceable' => $zones->isNotEmpty(),
            'matching_zone' => $primaryZone ? [
                'id' => $primaryZone->id,
                'name' => $primaryZone->name,
                'base_delivery_fee' => (float) $primaryZone->base_delivery_fee,
                'estimated_minutes' => (int) $primaryZone->estimated_minutes,
                'distance_km' => round(Geo::distanceKm(
                    $latitude,
                    $longitude,
                    (float) $primaryZone->center_latitude,
                    (float) $primaryZone->center_longitude,
                ), 2),
            ] : null,
            'zones' => $zones->map(fn (DeliveryZone $zone): array => [
                'id' => $zone->id,
                'name' => $zone->name,
                'distance_km' => round(Geo::distanceKm(
                    $latitude,
                    $longitude,
                    (float) $zone->center_latitude,
                    (float) $zone->center_longitude,
                ), 2),
            ])->values()->all(),
            'store_count' => $stores->count(),
            'stores' => $stores->map(fn (Store $store): array => [
                'id' => $store->id,
                'name' => $store->name,
                'address' => $store->address,
            ])->values()->all(),
        ];
    }

    public function resolveZoneForStoreAtPoint(Store $store, float $latitude, float $longitude): ?DeliveryZone
    {
        $zoneIds = $this->zonesCoveringPoint($latitude, $longitude)->pluck('id');

        if ($zoneIds->isEmpty()) {
            return null;
        }

        return $store->zones()
            ->whereIn('delivery_zones.id', $zoneIds)
            ->orderBy('delivery_zones.id')
            ->first();
    }
}
