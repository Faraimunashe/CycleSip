<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use Database\Seeders\LaratrustSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStoreInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_update_and_remove_store_inventory(): void
    {
        $this->seed(LaratrustSeeder::class);

        $admin = User::factory()->create();
        $admin->addRole('admin');

        $store = Store::factory()->create([
            'slug' => 'inventory-store',
            'address' => '1 Inventory Lane',
        ]);

        $product = Product::factory()->create([
            'slug' => 'inventory-product',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post("/admin/stores/{$store->id}/inventory", [
                'product_id' => $product->id,
                'price' => 12.50,
                'stock_quantity' => 20,
                'is_available' => true,
            ])
            ->assertRedirect("/admin/stores/{$store->id}");

        $inventory = StoreProduct::query()->where('store_id', $store->id)->firstOrFail();
        $this->assertSame($product->id, $inventory->product_id);
        $this->assertSame(20, $inventory->stock_quantity);

        $this->actingAs($admin)
            ->patch("/admin/stores/{$store->id}/inventory/{$inventory->id}", [
                'price' => 11.00,
                'stock_quantity' => 15,
                'is_available' => false,
                'promotion_price' => 9.50,
                'promotion_ends_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect("/admin/stores/{$store->id}");

        $inventory->refresh();
        $this->assertSame(15, $inventory->stock_quantity);
        $this->assertFalse($inventory->is_available);
        $this->assertEquals(9.50, (float) $inventory->promotion_price);

        $this->actingAs($admin)
            ->delete("/admin/stores/{$store->id}/inventory/{$inventory->id}")
            ->assertRedirect("/admin/stores/{$store->id}");

        $this->assertDatabaseMissing('store_products', ['id' => $inventory->id]);
    }

    public function test_admin_cannot_manage_inventory_for_another_store(): void
    {
        $this->seed(LaratrustSeeder::class);

        $admin = User::factory()->create();
        $admin->addRole('admin');

        $storeA = Store::factory()->create(['slug' => 'store-a', 'address' => 'A']);
        $storeB = Store::factory()->create(['slug' => 'store-b', 'address' => 'B']);
        $product = Product::factory()->create(['slug' => 'shared-product', 'is_active' => true]);

        $inventory = StoreProduct::query()->create([
            'store_id' => $storeB->id,
            'product_id' => $product->id,
            'price' => 5,
            'stock_quantity' => 10,
            'is_available' => true,
        ]);

        $this->actingAs($admin)
            ->patch("/admin/stores/{$storeA->id}/inventory/{$inventory->id}", [
                'price' => 6,
                'stock_quantity' => 8,
                'is_available' => true,
            ])
            ->assertNotFound();
    }
}
