<?php

namespace Tests\Feature;

use App\Models\DeliveryZone;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\LaratrustSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStoreZoneLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_link_store_to_delivery_zones(): void
    {
        $this->seed(LaratrustSeeder::class);

        $admin = User::factory()->create();
        $admin->addRole('admin');

        $zoneA = DeliveryZone::query()->create([
            'name' => 'Zone A',
            'slug' => 'zone-a',
            'center_latitude' => -17.8252,
            'center_longitude' => 31.0335,
            'radius_km' => 3,
            'base_delivery_fee' => 2.5,
            'distance_surcharge_per_km' => 0.75,
            'estimated_minutes' => 30,
            'is_active' => true,
        ]);

        $zoneB = DeliveryZone::query()->create([
            'name' => 'Zone B',
            'slug' => 'zone-b',
            'center_latitude' => -17.7805,
            'center_longitude' => 31.0520,
            'radius_km' => 4,
            'base_delivery_fee' => 3,
            'distance_surcharge_per_km' => 0.75,
            'estimated_minutes' => 35,
            'is_active' => true,
        ]);

        $store = Store::factory()->create([
            'slug' => 'linked-store',
            'address' => '1 Test Road',
        ]);

        $this->actingAs($admin)
            ->put("/admin/stores/{$store->id}", [
                'name' => $store->name,
                'slug' => $store->slug,
                'address' => $store->address,
                'commission_rate' => 15,
                'is_active' => true,
                'zone_ids' => [$zoneA->id, $zoneB->id],
            ])
            ->assertRedirect("/admin/stores/{$store->id}");

        $this->assertEqualsCanonicalizing(
            [$zoneA->id, $zoneB->id],
            $store->fresh()->zones()->pluck('delivery_zones.id')->all(),
        );
    }

    public function test_admin_can_link_stores_from_zone_edit(): void
    {
        $this->seed(LaratrustSeeder::class);

        $admin = User::factory()->create();
        $admin->addRole('admin');

        $zone = DeliveryZone::query()->create([
            'name' => 'Central Zone',
            'slug' => 'central-zone',
            'center_latitude' => -17.8252,
            'center_longitude' => 31.0335,
            'radius_km' => 3,
            'base_delivery_fee' => 2.5,
            'distance_surcharge_per_km' => 0.75,
            'estimated_minutes' => 30,
            'is_active' => true,
        ]);

        $store = Store::factory()->create([
            'slug' => 'zone-linked-store',
            'address' => '2 Test Road',
        ]);

        $this->actingAs($admin)
            ->put("/admin/zones/{$zone->id}", [
                'name' => $zone->name,
                'slug' => $zone->slug,
                'center_latitude' => $zone->center_latitude,
                'center_longitude' => $zone->center_longitude,
                'radius_km' => $zone->radius_km,
                'base_delivery_fee' => $zone->base_delivery_fee,
                'distance_surcharge_per_km' => $zone->distance_surcharge_per_km,
                'estimated_minutes' => $zone->estimated_minutes,
                'is_active' => true,
                'store_ids' => [$store->id],
            ])
            ->assertRedirect("/admin/zones/{$zone->id}");

        $this->assertTrue($store->fresh()->zones()->whereKey($zone->id)->exists());
    }
}
