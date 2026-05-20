<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RiderProfile;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    /**
     * Seed catalog data.
     */
    public function run(): void
    {
        $categories = collect([
            'beer',
            'whisky',
            'vodka',
            'wine',
            'spirits',
            'mixers',
            'snacks',
        ])->map(fn (string $name): ProductCategory => ProductCategory::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => Str::title($name), 'description' => Str::title($name).' category']
        ));

        $zones = [
            ['name' => 'University Core', 'center_latitude' => -17.8252, 'center_longitude' => 31.0335, 'radius_km' => 3.0],
            ['name' => 'Northern Urban', 'center_latitude' => -17.7805, 'center_longitude' => 31.0520, 'radius_km' => 4.0],
        ];

        $createdZones = collect($zones)->map(fn (array $zone): DeliveryZone => DeliveryZone::firstOrCreate(
            ['slug' => Str::slug($zone['name'])],
            $zone + [
                'base_delivery_fee' => 2.50,
                'distance_surcharge_per_km' => 0.75,
                'estimated_minutes' => 30,
                'is_active' => true,
            ]
        ));

        $stores = [
            [
                'name' => 'Campus Liquor Hub',
                'address' => '1 University Road',
                'phone' => '+263770100001',
                'logo_url' => 'https://images.unsplash.com/photo-1516455590571-18256e5bb9ff?auto=format&fit=crop&w=240&q=80',
            ],
            [
                'name' => 'Night Owl Drinks',
                'address' => '18 Oak Avenue',
                'phone' => '+263770100002',
                'logo_url' => 'https://images.unsplash.com/photo-1470337458703-46ad1756a187?auto=format&fit=crop&w=240&q=80',
            ],
        ];

        $products = [
            ['name' => 'Heineken 330ml', 'description' => 'Crisp lager beer.', 'brand' => 'Heineken', 'category' => 'beer'],
            ['name' => 'Savanna Dry 330ml', 'description' => 'Dry cider with apple notes.', 'brand' => 'Savanna', 'category' => 'beer'],
            ['name' => 'Smirnoff Vodka 750ml', 'description' => 'Triple distilled vodka.', 'brand' => 'Smirnoff', 'category' => 'vodka'],
            ['name' => 'Jameson Irish Whiskey 750ml', 'description' => 'Smooth blended whiskey.', 'brand' => 'Jameson', 'category' => 'whisky'],
            ['name' => 'Coca Cola 2L', 'description' => 'Popular mixer.', 'brand' => 'Coca Cola', 'category' => 'mixers'],
            ['name' => 'Salted Nuts Pack', 'description' => 'Crunchy snack.', 'brand' => 'CycleSip Select', 'category' => 'snacks'],
        ];

        $createdStores = collect($stores)->map(function (array $store): Store {
            return Store::firstOrCreate(
                ['slug' => Str::slug($store['name'])],
                $store + [
                    'opening_time' => '09:00',
                    'closing_time' => '22:00',
                    'commission_rate' => 15,
                    'is_active' => true,
                ]
            );
        });

        $createdProducts = collect($products)->map(function (array $product) use ($categories): Product {
            $category = $categories->firstWhere('slug', Str::slug($product['category']));

            return Product::firstOrCreate(
                ['slug' => Str::slug($product['name'])],
                [
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'brand' => $product['brand'],
                    'product_category_id' => $category?->id,
                    'is_featured' => in_array($product['category'], ['beer', 'vodka'], true),
                    'is_promoted' => in_array($product['category'], ['beer'], true),
                    'is_active' => true,
                ]
            );
        });

        foreach ($createdStores as $store) {
            $store->zones()->syncWithoutDetaching($createdZones->pluck('id')->all());

            foreach ($createdProducts as $index => $product) {
                $store->inventory()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'price' => 4.50 + ($index * 1.25),
                        'stock_quantity' => 20 + ($index * 5),
                        'is_available' => true,
                        'promotion_price' => $index % 2 === 0 ? 4.00 + ($index * 1.10) : null,
                        'promotion_ends_at' => $index % 2 === 0 ? now()->addDays(5) : null,
                    ]
                );
            }
        }

        $riders = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'rider'))->get();

        foreach ($riders as $rider) {
            $profile = RiderProfile::query()->firstOrCreate(
                ['user_id' => $rider->id],
                [
                    'approval_status' => 'approved',
                    'is_online' => (bool) random_int(0, 1),
                    'vehicle_type' => 'bicycle',
                    'bicycle_model' => 'Roadster '.random_int(1, 9),
                    'emergency_contact_name' => 'Emergency Contact',
                    'emergency_contact_phone' => '+263771000000',
                    'acceptance_rate' => random_int(75, 95),
                    'cancellation_rate' => random_int(1, 10),
                    'completed_deliveries' => random_int(10, 140),
                    'approved_at' => now(),
                    'approved_by' => User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'super-admin']))->value('id'),
                ]
            );

            $profile->zones()->syncWithoutDetaching($createdZones->pluck('id')->all());
        }
    }
}
