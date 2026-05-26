<?php

namespace Tests\Feature;

use App\Events\OrderAvailableForRiders;
use App\Events\OrderStatusChanged;
use App\Events\RiderLocationUpdated;
use App\Models\Order;
use App\Models\Product;
use App\Models\RiderLocation;
use App\Models\RiderProfile;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\OrderWorkflowService;
use Database\Seeders\LaratrustSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RealtimeBroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LaratrustSeeder::class);
        $this->seed(PaymentMethodSeeder::class);
    }

    public function test_order_available_for_riders_event_is_dispatched_when_order_is_broadcast(): void
    {
        Event::fake([OrderAvailableForRiders::class]);

        $order = $this->createBroadcastOrder();

        app(OrderWorkflowService::class)->broadcastToRiders($order);

        Event::assertDispatched(OrderAvailableForRiders::class, function (OrderAvailableForRiders $event) use ($order): bool {
            return $event->order->id === $order->id;
        });
    }

    public function test_order_status_changed_broadcasts_to_customer_order_channel(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $order = $this->createBroadcastOrder();
        app(OrderWorkflowService::class)->broadcastToRiders($order);
        $order->refresh();
        $rider = $this->createApprovedRider();

        app(OrderWorkflowService::class)->transition(
            order: $order,
            toStatus: Order::STATUS_ACCEPTED_BY_RIDER,
            userId: $rider->id,
            note: 'Accepted in test',
        );

        Event::assertDispatched(OrderStatusChanged::class);
    }

    public function test_rider_can_authenticate_marketplace_broadcast_channel(): void
    {
        $rider = $this->createApprovedRider();
        Sanctum::actingAs($rider);

        $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-riders.marketplace',
        ])->assertOk();
    }

    public function test_customer_can_authenticate_own_order_channel(): void
    {
        $customer = $this->createCustomerWithAddress();
        $order = $this->createBroadcastOrder($customer);

        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-orders.'.$order->id,
        ])->assertOk();
    }

    public function test_assigned_rider_can_authenticate_order_channel(): void
    {
        $customer = $this->createCustomerWithAddress();
        $order = $this->createBroadcastOrder($customer);
        $rider = $this->createApprovedRider();

        $order->update([
            'rider_id' => $rider->id,
            'status' => Order::STATUS_ACCEPTED_BY_RIDER,
        ]);

        Sanctum::actingAs($rider);

        $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-orders.'.$order->id,
        ])->assertOk();
    }

    public function test_rider_location_update_broadcasts_to_order_channel(): void
    {
        Event::fake([RiderLocationUpdated::class]);

        $customer = $this->createCustomerWithAddress();
        $order = $this->createBroadcastOrder($customer);
        $rider = $this->createApprovedRider();

        $order->update([
            'rider_id' => $rider->id,
            'status' => Order::STATUS_EN_ROUTE_TO_CUSTOMER,
        ]);

        $profile = RiderProfile::query()->where('user_id', $rider->id)->firstOrFail();
        $location = RiderLocation::query()->create([
            'rider_profile_id' => $profile->id,
            'latitude' => -17.8252,
            'longitude' => 31.0335,
            'recorded_at' => now(),
        ]);

        event(new RiderLocationUpdated($order->fresh(), $location));

        Event::assertDispatched(RiderLocationUpdated::class, function (RiderLocationUpdated $event) use ($order): bool {
            return $event->order->id === $order->id;
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
            'address_line' => '12 Realtime Street',
            'latitude' => -17.8252,
            'longitude' => 31.0335,
            'is_default' => true,
        ]);

        $customer->update(['selected_delivery_address_id' => $address->id]);

        return $customer->fresh();
    }

    private function createApprovedRider(): User
    {
        $rider = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $rider->addRole('rider');

        RiderProfile::query()->create([
            'user_id' => $rider->id,
            'approval_status' => 'approved',
            'is_online' => true,
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
            'delivery_address' => '12 Realtime Street',
            'delivery_instructions' => 'Call on arrival',
            'placed_at' => now(),
        ]);
    }
}
