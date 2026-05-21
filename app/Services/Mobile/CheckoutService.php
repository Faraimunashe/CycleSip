<?php

namespace App\Services\Mobile;

use App\Models\Order;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\ActivityLogger;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly DeliveryAddressService $deliveryAddressService,
        private readonly OrderWorkflowService $orderWorkflowService,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(User $user): array
    {
        $cart = $this->cartService->payload($user);
        $selectedAddress = $this->deliveryAddressService->selected($user);

        return [
            'cart' => $cart,
            'selected_address' => $selectedAddress ? [
                'id' => $selectedAddress->id,
                'label' => $selectedAddress->label,
                'address_line' => $selectedAddress->address_line,
                'latitude' => $selectedAddress->latitude,
                'longitude' => $selectedAddress->longitude,
            ] : null,
            'payment_methods' => ['cash', 'ecocash', 'innbucks', 'swipe'],
            'estimated_order_count' => count($cart['stores']),
            'estimated_total' => round((float) $cart['subtotal'] + (count($cart['stores']) * 2.50), 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return Collection<int, Order>
     */
    public function process(User $user, array $validated, ?Request $request = null): Collection
    {
        $selectedAddress = $this->deliveryAddressService->selected($user);

        if (! $selectedAddress) {
            throw ValidationException::withMessages([
                'delivery_address' => 'Please select a delivery address before placing orders.',
            ]);
        }

        $cartLines = $this->cartService->lines($user);

        if ($cartLines->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        if ($validated['store_scope'] === 'single') {
            if (empty($validated['selected_store_id'])) {
                throw ValidationException::withMessages([
                    'selected_store_id' => 'Please select a store.',
                ]);
            }

            $cartLines = $cartLines
                ->filter(fn (array $line): bool => $line['store_product']->store_id === (int) $validated['selected_store_id'])
                ->values();

            if ($cartLines->isEmpty()) {
                throw ValidationException::withMessages([
                    'selected_store_id' => 'No cart items found for the selected store.',
                ]);
            }
        }

        $stockErrors = $cartLines
            ->filter(fn (array $line): bool => ! $line['store_product']->is_available || $line['quantity'] > (int) $line['store_product']->stock_quantity)
            ->map(fn (array $line): string => "{$line['store_product']->product->name} is out of stock or has limited quantity.")
            ->values();

        if ($stockErrors->isNotEmpty()) {
            throw ValidationException::withMessages([
                'cart' => $stockErrors->implode(' '),
            ]);
        }

        $ordersCreated = collect();

        DB::transaction(function () use ($user, $validated, $cartLines, $selectedAddress, $ordersCreated, $request): void {
            $groupedByStore = $cartLines->groupBy(fn (array $line): int => (int) $line['store_product']->store_id);

            foreach ($groupedByStore as $storeId => $storeLines) {
                $order = $this->createOrderForStore(
                    user: $user,
                    storeId: (int) $storeId,
                    storeLines: $storeLines,
                    validated: $validated,
                    selectedAddress: $selectedAddress,
                    request: $request,
                );

                $ordersCreated->push($order);
            }
        });

        $this->cartService->removeLines($user, $cartLines);

        return $ordersCreated;
    }

    /**
     * @param  Collection<int, array{store_product: \App\Models\StoreProduct, quantity: int, unit_price: float}>  $storeLines
     * @param  array<string, mixed>  $validated
     */
    private function createOrderForStore(
        User $user,
        int $storeId,
        Collection $storeLines,
        array $validated,
        UserAddress $selectedAddress,
        ?Request $request,
    ): Order {
        $subtotal = round($storeLines->sum(fn (array $line): float => $line['quantity'] * $line['unit_price']), 2);
        $deliveryFee = 2.50;
        $platformCommission = round($subtotal * 0.08, 2);

        $order = Order::create([
            'user_id' => $user->id,
            'store_id' => $storeId,
            'status' => Order::STATUS_PENDING,
            'payment_method' => $validated['payment_method'],
            'subtotal_amount' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'platform_commission' => $platformCommission,
            'total_amount' => round($subtotal + $deliveryFee, 2),
            'payment_status' => $validated['payment_method'] === 'cash' ? 'pending_collection' : 'pending',
            'delivery_address_id' => $selectedAddress->id,
            'delivery_address' => $selectedAddress->address_line,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'recipient_name' => ($validated['delivering_for_someone'] ?? false) ? ($validated['recipient_name'] ?? null) : null,
            'recipient_phone' => ($validated['delivering_for_someone'] ?? false) ? ($validated['recipient_phone'] ?? null) : null,
            'notes' => $validated['notes'] ?? null,
            'delivery_instructions' => $validated['delivery_instructions'],
            'placed_at' => now(),
        ]);

        foreach ($storeLines as $line) {
            $storeProduct = $line['store_product'];
            $quantity = $line['quantity'];
            $unitPrice = $line['unit_price'];

            $order->items()->create([
                'product_id' => $storeProduct->product_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($unitPrice * $quantity, 2),
            ]);

            $storeProduct->decrement('stock_quantity', $quantity);
        }

        $order->timeline()->create([
            'status' => Order::STATUS_PENDING,
            'note' => 'Order placed by customer via mobile checkout',
            'changed_by' => $user->id,
        ]);

        $this->activityLogger->log(
            event: 'order.created',
            subject: $order,
            userId: $user->id,
            metadata: [
                'store_id' => $storeId,
                'items_count' => $storeLines->count(),
                'payment_method' => $validated['payment_method'],
                'channel' => 'mobile_api',
            ],
            request: $request,
        );

        $this->orderWorkflowService->broadcastToRiders($order);

        return $order;
    }
}
