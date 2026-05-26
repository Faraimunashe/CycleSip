<?php

namespace App\Support;

class Geo
{
    private const EARTH_RADIUS_KM = 6371.0;

    public static function distanceKm(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $latDelta = deg2rad($toLatitude - $fromLatitude);
        $lngDelta = deg2rad($toLongitude - $fromLongitude);

        $fromLatRad = deg2rad($fromLatitude);
        $toLatRad = deg2rad($toLatitude);

        $a = sin($latDelta / 2) ** 2
            + cos($fromLatRad) * cos($toLatRad) * sin($lngDelta / 2) ** 2;

        return self::EARTH_RADIUS_KM * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public static function isWithinRadius(
        float $pointLatitude,
        float $pointLongitude,
        float $centerLatitude,
        float $centerLongitude,
        float $radiusKm,
    ): bool {
        return self::distanceKm($pointLatitude, $pointLongitude, $centerLatitude, $centerLongitude) <= $radiusKm;
    }
}
