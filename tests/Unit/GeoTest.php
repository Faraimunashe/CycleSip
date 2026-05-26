<?php

namespace Tests\Unit;

use App\Support\Geo;
use PHPUnit\Framework\TestCase;

class GeoTest extends TestCase
{
    public function test_distance_between_same_point_is_zero(): void
    {
        $this->assertSame(0.0, Geo::distanceKm(-17.8252, 31.0335, -17.8252, 31.0335));
    }

    public function test_point_within_radius(): void
    {
        $this->assertTrue(Geo::isWithinRadius(
            -17.8252,
            31.0335,
            -17.8252,
            31.0335,
            3.0,
        ));
    }
}
