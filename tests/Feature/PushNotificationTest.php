<?php

namespace Tests\Feature;

use App\Events\OrderAvailableForRiders;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\Product;
use App\Models\PushToken;
use App\Models\RiderProfile;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\ExpoPushService;
use App\Services\OrderWorkflowService;
use Database\Seeders\LaratrustSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LaratrustSeeder::class);
        $this->seed(PaymentMethodSeeder::class);
    }

    public function test_authenticated_user_can_register_push_token(): void
    {
        $customer = $this->createCustomerWithAddress();
        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/push-tokens', [
            'token' => 'ExponentPushToken[test-token-123]',
            'platform' => 'android',
            'device_name' => 'Pixel Test',
        ])->assertOk();

        $this->assertDatabaseHas('push_tokens', [
            'user_id' => $customer->id,
            'token' => 'ExponentPushToken[test-token-123]',
            'platform' => 'android',
        ]);
    }

    public function test_order_status_change_sends_expo_push_to_customer(): void
    {
        Http::fake([
            'exp.host/*' => Http::response([
                'data' => [
                    ['status' => 'ok', 'id' => 'ticket-1'],
                ],
            ], 200),
        ]);

        $customer = $this->createCustomerWithAddress();
        PushToken::query()->create([
            'user_id' => $customer->id,
            'token' => 'ExponentPushToken[customer-token]',
            'platform' => 'android',
        ]);

        $order = $this->createBroadcastOrder($customer);
        app(OrderWorkflowService::class)->broadcastToRiders($order);

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return str_contains($request->url(), 'exp.host')
                && is_array($payload)
                && ($payload[0]['to'] ?? null) === 'ExponentPushToken[customer-token]'
                && ($payload[0]['data']['type'] ?? null) === 'order_status';
        });
    }

    public function test_order_available_sends_expo_push_to_online_riders(): void
    {
        Http::fake([
            'exp.host/*' => Http::response([
                'data' => [
                    ['status' => 'ok', 'id' => 'ticket-1'],
                ],
            ], 200),
        ]);

        $rider = $this->createApprovedRider(isOnline: true);
        PushToken::query()->create([
            'user_id' => $rider->id,
            'token' => 'ExponentPushToken[rider-token]',
            'platform' => 'android',
        ]);

        $order = $this->createBroadcastOrder();
        event(new OrderAvailableForRiders($order));

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return str_contains($request->url(), 'exp.host')
                && is_array($payload)
                && ($payload[0]['to'] ?? null) === 'ExponentPushToken[rider-token]'
                && ($payload[0]['data']['type'] ?? null) === 'order_available';
        });
    }

    private function createCustomerWithAddress(): User
    {
        $customer = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $customer->addRole('customer');

        $address = UserAddress::query()->create([
            'user_id' => $customer->id,
            'label' => 'Home',
            'address_line' => '12 Push Street',
            'latitude' => -17.8252,
            'longitude' => 31.0335,
            'is_default' => true,
        ]);

        $customer->update(['selected_delivery_address_id' => $address->id]);

        return $customer->fresh();
    }

    private function createApprovedRider(bool $isOnline = true): User
    {
        $rider = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $rider->addRole('rider');

        RiderProfile::query()->create([
            'user_id' => $rider->id,
            'approval_status' => 'approved',
            'is_online' => $isOnline,
        ]);

        return $rider->fresh();
    }

    private function createBroadcastOrder(?User $customer = null): Order
    {
        $customer ??= $this->createCustomerWithAddress();

        $store = Store::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['is_active' => true]);
        StoreProduct::query()->create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'price' => 12.00,
            'stock_quantity' => 5,
            'is_available' => true,
        ]);

        return Order::query()->create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'cash',
            'payment_status' => 'pending_collection',
            'subtotal_amount' => 12.00,
            'delivery_fee' => 2.50,
            'platform_commission' => 0.96,
            'total_amount' => 14.50,
            'delivery_address' => '12 Push Street',
            'delivery_instructions' => 'Call on arrival',
            'placed_at' => now(),
        ]);
    }
}
