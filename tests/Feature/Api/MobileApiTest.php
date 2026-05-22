<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use App\Models\UserAddress;
use Database\Seeders\LaratrustSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LaratrustSeeder::class);
    }

    public function test_register_returns_sanctum_token_and_customer_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Mobile Customer',
            'email' => 'mobile@example.com',
            'phone' => '+263771234567',
            'date_of_birth' => now()->subYears(25)->format('Y-m-d'),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.roles', ['customer'])
            ->assertJsonPath('data.user.address_selection_required', false);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_returns_token_with_roles_for_rider_mode_switching(): void
    {
        $user = User::factory()->create([
            'email' => 'dual@example.com',
            'password' => 'password123',
            'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
            'age_verified_at' => now(),
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $user->addRole('customer');
        $user->addRole('rider');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'dual@example.com',
            'password' => 'password123',
            'device_name' => 'iphone',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user.has_rider_role', true)
            ->assertJsonPath('data.user.can_use_rider_mode', true)
            ->assertJsonCount(2, 'data.user.roles');
    }

    public function test_unverified_customer_cannot_access_catalog(): void
    {
        $customer = User::factory()->unverified()->create([
            'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
            'age_verified_at' => now(),
            'status' => 'active',
        ]);
        $customer->addRole('customer');

        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/catalog')
            ->assertForbidden()
            ->assertJsonPath('code', 'email_verification_required');
    }

    public function test_verified_customer_must_select_address_before_catalog(): void
    {
        $customer = $this->verifiedCustomer();

        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/catalog')
            ->assertForbidden()
            ->assertJsonPath('code', 'delivery_address_required');
    }

    public function test_catalog_returns_filters_and_supports_search(): void
    {
        $customer = $this->verifiedCustomer(withAddress: true);
        $cola = $this->createStoreProduct(stock: 5, price: 10.00);
        $cola->product->update(['name' => 'Cola Classic', 'image_url' => '/storage/products/cola.jpg']);
        $wine = $this->createStoreProduct(stock: 3, price: 20.00);
        $wine->product->update(['name' => 'House Red Wine', 'image_url' => '/storage/products/wine.jpg']);

        Sanctum::actingAs($customer);

        $catalog = $this->getJson('/api/v1/catalog')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'stores',
                    'filters' => ['categories', 'stores'],
                    'cart_summary',
                ],
            ]);

        $filterStoreNames = collect($catalog->json('data.filters.stores'))->pluck('name');
        $this->assertTrue($filterStoreNames->contains($cola->store->name));
        $this->assertTrue($filterStoreNames->contains($wine->store->name));

        $searchResponse = $this->getJson('/api/v1/catalog?search=cola')->assertOk();
        $productNames = collect($searchResponse->json('data.stores'))
            ->flatMap(fn (array $store): array => $store['inventory'])
            ->pluck('product_name');

        $this->assertTrue($productNames->contains('Cola Classic'));
        $this->assertFalse($productNames->contains('House Red Wine'));

        $imageUrl = $searchResponse->json('data.stores.0.inventory.0.image_url');
        $this->assertIsString($imageUrl);
        $this->assertStringStartsWith('http', $imageUrl);
        $this->assertStringContainsString('/storage/products/cola.jpg', $imageUrl);
    }

    public function test_customer_can_manage_cart_and_checkout(): void
    {
        $customer = $this->verifiedCustomer(withAddress: true);
        $storeProduct = $this->createStoreProduct(stock: 5, price: 10.00);

        Sanctum::actingAs($customer);

        $cartResponse = $this->postJson('/api/v1/cart/items', [
            'store_product_id' => $storeProduct->id,
            'quantity' => 2,
        ]);

        $cartResponse->assertOk()
            ->assertJsonPath('data.item_count', 2);
        $this->assertSame(20.0, (float) $cartResponse->json('data.subtotal'));

        $this->patchJson("/api/v1/cart/items/{$storeProduct->id}", ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('data.item_count', 3);

        $this->deleteJson("/api/v1/cart/items/{$storeProduct->id}")
            ->assertOk()
            ->assertJsonPath('data.line_count', 0);

        $this->postJson('/api/v1/cart/items', [
            'store_product_id' => $storeProduct->id,
            'quantity' => 2,
        ]);

        $checkout = $this->postJson('/api/v1/checkout', [
            'delivery_instructions' => 'Leave at gate',
            'payment_method' => 'cash',
            'store_scope' => 'all',
        ]);

        $checkout
            ->assertCreated()
            ->assertJsonCount(1, 'data.order_ids');

        $order = Order::query()->findOrFail($checkout->json('data.order_ids.0'));
        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame(Order::STATUS_BROADCAST_TO_RIDERS, $order->status);
        $this->assertSame(0, $customer->cartItems()->count());

        $this->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.order.id', $order->id)
            ->assertJsonStructure([
                'data' => [
                    'order' => [
                        'id',
                        'status',
                        'items',
                        'timeline',
                        'order_rating',
                        'rider_rating',
                        'can_rate',
                    ],
                ],
            ]);
    }

    public function test_rider_can_access_dashboard_and_accept_order(): void
    {
        $customer = $this->verifiedCustomer(withAddress: true);
        $storeProduct = $this->createStoreProduct(stock: 3, price: 12.00);

        Sanctum::actingAs($customer);
        $this->postJson('/api/v1/cart/items', [
            'store_product_id' => $storeProduct->id,
            'quantity' => 1,
        ]);
        $orderId = $this->postJson('/api/v1/checkout', [
            'delivery_instructions' => 'Call on arrival',
            'payment_method' => 'ecocash',
            'store_scope' => 'all',
        ])->json('data.order_ids.0');

        $rider = User::factory()->create([
            'email_verified_at' => now(),
            'date_of_birth' => now()->subYears(28)->format('Y-m-d'),
            'age_verified_at' => now(),
            'status' => 'active',
        ]);
        $rider->addRole('rider');

        Sanctum::actingAs($rider);

        $this->getJson('/api/v1/rider/dashboard')
            ->assertOk()
            ->assertJsonPath('data.active_orders_total', 0);

        $this->getJson('/api/v1/rider/orders/available')->assertOk();
        $this->getJson('/api/v1/rider/orders/active')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 0);

        $this->postJson("/api/v1/rider/orders/{$orderId}/accept")
            ->assertOk()
            ->assertJsonPath('data.order.status', Order::STATUS_ACCEPTED_BY_RIDER);

        $this->getJson('/api/v1/rider/orders/active')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.orders.0.id', $orderId);

        $this->getJson('/api/v1/rider/dashboard')
            ->assertOk()
            ->assertJsonPath('data.active_orders_total', 1);

        $this->getJson("/api/v1/rider/orders/{$orderId}")
            ->assertOk()
            ->assertJsonPath('data.order.id', $orderId)
            ->assertJsonPath('data.order.can_update_status', true)
            ->assertJsonStructure([
                'data' => [
                    'order' => [
                        'items',
                        'timeline',
                        'next_status_options',
                        'order_rating',
                        'rider_rating',
                    ],
                ],
            ]);
    }

    public function test_customer_without_rider_role_cannot_access_rider_routes(): void
    {
        Sanctum::actingAs($this->verifiedCustomer(withAddress: true));

        $this->getJson('/api/v1/rider/dashboard')
            ->assertForbidden()
            ->assertJsonPath('code', 'rider_role_required');
    }

    private function verifiedCustomer(bool $withAddress = false): User
    {
        $customer = User::factory()->create([
            'date_of_birth' => now()->subYears(27)->format('Y-m-d'),
            'age_verified_at' => now(),
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $customer->addRole('customer');

        if ($withAddress) {
            $address = UserAddress::query()->create([
                'user_id' => $customer->id,
                'label' => 'Home',
                'address_line' => '12 Test Street',
                'latitude' => -17.8252,
                'longitude' => 31.0335,
                'is_default' => true,
            ]);

            $customer->update(['selected_delivery_address_id' => $address->id]);
        }

        return $customer->fresh();
    }

    private function createStoreProduct(int $stock, float $price): StoreProduct
    {
        $store = Store::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['is_active' => true]);

        return StoreProduct::query()->create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'price' => $price,
            'stock_quantity' => $stock,
            'is_available' => true,
        ]);
    }
}
