<?php

namespace App\Services;

use App\Events\RiderLocationUpdated;
use App\Models\Order;
use App\Models\RiderLocation;
use App\Models\RiderProfile;

class RiderLocationService
{
    /**
     * @return list<string>
     */
    public function trackableOrderStatuses(): array
    {
        return [
            Order::STATUS_ACCEPTED_BY_RIDER,
            Order::STATUS_EN_ROUTE_TO_STORE,
            Order::STATUS_VERIFYING_STOCK,
            Order::STATUS_COLLECTING_ITEMS,
            Order::STATUS_ADJUSTED,
            Order::STATUS_EN_ROUTE_TO_CUSTOMER,
        ];
    }

    public function record(RiderProfile $profile, float $latitude, float $longitude): RiderLocation
    {
        $location = $profile->locations()->create([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'recorded_at' => now(),
        ]);

        $order = Order::query()
            ->where('rider_id', $profile->user_id)
            ->whereIn('status', $this->trackableOrderStatuses())
            ->latest('id')
            ->first();

        if ($order !== null) {
            event(new RiderLocationUpdated($order, $location));
        }

        return $location;
    }

    /**
     * @return array{latitude: float, longitude: float, recorded_at: string|null}|null
     */
    public function latestForOrder(Order $order): ?array
    {
        if ($order->rider_id === null) {
            return null;
        }

        $profile = RiderProfile::query()->where('user_id', $order->rider_id)->first();

        if ($profile === null) {
            return null;
        }

        $location = $profile->locations()->latest('recorded_at')->first();

        if ($location === null) {
            return null;
        }

        return [
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'recorded_at' => optional($location->recorded_at)?->toIso8601String(),
        ];
    }
}
