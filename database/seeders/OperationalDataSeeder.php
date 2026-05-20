<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\RiderEarning;
use App\Models\RiderProfile;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OperationalDataSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'customer'))->limit(8)->get();
        $riders = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'rider'))->get();
        $stores = Store::query()->with('zones')->get();

        if ($customers->isEmpty() || $stores->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 24; $i++) {
            $customer = $customers->random();
            $store = $stores->random();
            $rider = $riders->isNotEmpty() ? $riders->random() : null;
            $zoneId = $store->zones->first()?->id;

            $status = fake()->randomElement([
                Order::STATUS_COMPLETED,
                Order::STATUS_EN_ROUTE_TO_CUSTOMER,
                Order::STATUS_BROADCAST_TO_RIDERS,
                Order::STATUS_CANCELLED,
            ]);

            $subtotal = fake()->randomFloat(2, 8, 35);
            $deliveryFee = fake()->randomFloat(2, 1, 4);

            $order = Order::create([
                'user_id' => $customer->id,
                'rider_id' => $rider?->id,
                'store_id' => $store->id,
                'delivery_zone_id' => $zoneId,
                'status' => $status,
                'payment_method' => fake()->randomElement(['cash', 'ecocash', 'innbucks', 'swipe']),
                'subtotal_amount' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'platform_commission' => round($subtotal * 0.08, 2),
                'total_amount' => $subtotal + $deliveryFee,
                'payment_status' => $status === Order::STATUS_COMPLETED ? 'paid' : 'pending',
                'delivery_address' => fake()->streetAddress(),
                'customer_phone' => $customer->phone,
                'notes' => null,
                'placed_at' => now()->subHours(random_int(1, 120)),
                'accepted_at' => $rider ? now()->subHours(random_int(1, 72)) : null,
                'delivered_at' => $status === Order::STATUS_COMPLETED ? now()->subHours(random_int(1, 24)) : null,
                'completed_at' => $status === Order::STATUS_COMPLETED ? now()->subHours(random_int(1, 12)) : null,
                'cancelled_at' => $status === Order::STATUS_CANCELLED ? now()->subHours(random_int(1, 12)) : null,
                'cancellation_reason' => $status === Order::STATUS_CANCELLED ? 'Customer cancelled' : null,
                'created_at' => now()->subHours(random_int(1, 150)),
                'updated_at' => now()->subHours(random_int(0, 120)),
            ]);

            foreach ($store->inventory()->inRandomOrder()->limit(random_int(1, 3))->get() as $inventory) {
                $quantity = random_int(1, 2);
                $price = (float) ($inventory->promotion_price ?: $inventory->price);
                $order->items()->create([
                    'product_id' => $inventory->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'line_total' => round($quantity * $price, 2),
                ]);
            }

            $order->timeline()->create([
                'status' => Order::STATUS_PENDING,
                'note' => 'Order created from seeder',
                'changed_by' => $customer->id,
            ]);
            $order->timeline()->create([
                'status' => $status,
                'note' => 'Operational transition',
                'changed_by' => $rider?->id,
            ]);

            Transaction::create([
                'order_id' => $order->id,
                'user_id' => $customer->id,
                'reference' => 'TXN-'.Str::upper(Str::random(10)),
                'method' => $order->payment_method,
                'status' => $status === Order::STATUS_COMPLETED ? 'success' : 'pending',
                'amount' => $order->total_amount,
                'currency' => 'USD',
            ]);

            if ($rider && $status === Order::STATUS_COMPLETED) {
                $riderProfile = RiderProfile::query()->where('user_id', $rider->id)->first();

                if ($riderProfile) {
                    RiderEarning::create([
                        'rider_profile_id' => $riderProfile->id,
                        'order_id' => $order->id,
                        'gross_amount' => 3.50,
                        'platform_fee' => 0.50,
                        'net_amount' => 3.00,
                        'status' => 'settled',
                        'settled_at' => now()->subHours(random_int(1, 6)),
                    ]);
                }
            }
        }
    }
}
