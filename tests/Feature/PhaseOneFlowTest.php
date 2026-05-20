<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\UserAddress;
use App\Models\User;
use Database\Seeders\LaratrustSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_products_page(): void
    {
        $response = $this->get('/products');

        $response->assertRedirect('/login');
    }

    public function test_user_can_register_and_get_customer_role(): void
    {
        $this->seed(LaratrustSeeder::class);

        $response = $this->post('/register', [
            'name' => 'New Customer',
            'email' => 'newcustomer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/addresses/select');
        $this->assertAuthenticated();

        $user = User::where('email', 'newcustomer@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('customer'));
    }

    public function test_customer_can_place_order(): void
    {
        $this->seed(LaratrustSeeder::class);

        $customer = User::factory()->create();
        $customer->addRole('customer');
        $address = UserAddress::create([
            'user_id' => $customer->id,
            'label' => 'Home',
            'address_line' => '12 Test Lane',
            'is_default' => true,
        ]);

        $store = Store::factory()->create();
        $product = Product::factory()->create();
        $storeProduct = StoreProduct::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'price' => 7.50,
            'stock_quantity' => 8,
        ]);

        $response = $this
            ->withSession([
                'selected_delivery_address_id' => $address->id,
                'address_selection_required' => false,
            ])
            ->actingAs($customer)
            ->post('/orders', [
            'store_id' => $store->id,
            'delivery_instructions' => 'Call once on arrival',
            'notes' => 'Ring once',
            'payment_method' => 'cash',
            'items' => [
                [
                    'store_product_id' => $storeProduct->id,
                    'quantity' => 2,
                ],
            ],
            ]);

        $response->assertRedirect('/orders');

        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => Order::STATUS_BROADCAST_TO_RIDERS,
            'payment_method' => 'cash',
            'delivery_address' => '12 Test Lane',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('store_products', [
            'id' => $storeProduct->id,
            'stock_quantity' => 6,
        ]);
    }

    public function test_non_admin_cannot_access_admin_orders(): void
    {
        $this->seed(LaratrustSeeder::class);

        $customer = User::factory()->create();
        $customer->addRole('customer');

        $response = $this->actingAs($customer)->get('/admin/orders');

        $response->assertForbidden();
    }

    public function test_admin_can_update_order_status(): void
    {
        $this->seed(LaratrustSeeder::class);

        $admin = User::factory()->create();
        $admin->addRole('admin');

        $customer = User::factory()->create();
        $customer->addRole('customer');

        $store = Store::factory()->create();
        $order = Order::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => Order::STATUS_BROADCAST_TO_RIDERS,
            'payment_method' => 'cash',
            'total_amount' => 11.00,
            'delivery_address' => '10 Main Street',
            'placed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->patch("/admin/orders/{$order->id}/status", [
            'status' => Order::STATUS_ACCEPTED_BY_RIDER,
        ]);

        $response->assertRedirect("/admin/orders/{$order->id}");

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_ACCEPTED_BY_RIDER,
        ]);
    }
}
