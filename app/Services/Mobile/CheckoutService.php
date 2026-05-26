<?php

namespace App\Services\Mobile;

use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserAddress;
use App\Rules\ZimbabwePhone;
use App\Services\ActivityLogger;
use App\Services\DeliveryZoneService;
use App\Services\OrderWorkflowService;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentMethodService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly DeliveryAddressService $deliveryAddressService,
        private readonly DeliveryZoneService $deliveryZoneService,
        private readonly OrderWorkflowService $orderWorkflowService,
        private readonly ActivityLogger $activityLogger,
        private readonly PaymentMethodService $paymentMethodService,
        private readonly PaymentGatewayManager $paymentGatewayManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(User $user): array
    {
        $cart = $this->cartService->payload($user);
        $selectedAddress = $this->deliveryAddressService->selected($user);
        $estimatedTotal = $this->estimateTotal($user, $selectedAddress, $cart);

        return [
            'cart' => $cart,
            'selected_address' => $selectedAddress ? [
                'id' => $selectedAddress->id,
                'label' => $selectedAddress->label,
                'address_line' => $selectedAddress->address_line,
                'latitude' => $selectedAddress->latitude,
                'longitude' => $selectedAddress->longitude,
            ] : null,
            'payment_methods' => $this->paymentMethodService->toCheckoutOptions(),
            'estimated_order_count' => collect($cart['stores'])->count(),
            'estimated_total' => $estimatedTotal,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return Collection<int, Order>
     */
    public function processOnDelivery(User $user, array $validated, ?Request $request = null): Collection
    {
        $paymentMethod = $this->paymentMethodService->findEnabledByCode($validated['payment_method']);

        if ($paymentMethod->isPrepay()) {
            throw ValidationException::withMessages([
                'payment_method' => 'This payment method must be paid before checkout completes.',
            ]);
        }

        [$selectedAddress, $cartLines] = $this->prepareCartLines($user, $validated);

        return $this->createOrdersFromCartLines(
            user: $user,
            validated: $validated,
            selectedAddress: $selectedAddress,
            cartLines: $cartLines,
            paymentMethod: $paymentMethod,
            request: $request,
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{checkout_session: CheckoutSession, orders: Collection<int, Order>}
     */
    public function processPrepaid(User $user, array $validated, ?Request $request = null): array
    {
        $paymentMethod = $this->paymentMethodService->findEnabledByCode($validated['payment_method']);

        if ($paymentMethod->isOnDelivery()) {
            throw ValidationException::withMessages([
                'payment_method' => 'This payment method is collected on delivery.',
            ]);
        }

        if ($paymentMethod->requires_phone) {
            $msisdn = $validated['customer_msisdn'] ?? $user->phone;

            if (! $msisdn) {
                throw ValidationException::withMessages([
                    'customer_msisdn' => 'A Zimbabwe mobile number is required for this payment method.',
                ]);
            }

            $validated['customer_msisdn'] = ZimbabwePhone::toEcocashMsisdn($msisdn);
        }

        [$selectedAddress, $cartLines] = $this->prepareCartLines($user, $validated);
        $amount = $this->estimateTotalFromLines($selectedAddress, $cartLines);
        $session = CheckoutSession::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_method_code' => $paymentMethod->code,
            'amount' => $amount,
            'currency' => config('ecocash.currency', 'USD'),
            'status' => CheckoutSession::STATUS_AWAITING_PAYMENT,
            'customer_msisdn' => $validated['customer_msisdn'] ?? null,
            'gateway' => $paymentMethod->gateway,
            'gateway_reference' => null,
            'cart_snapshot' => $this->serializeCartLines($cartLines),
            'checkout_payload' => $validated,
            'expires_at' => now()->addMinutes(15),
        ]);

        try {
            $session->update(['status' => CheckoutSession::STATUS_PROCESSING]);
            $gateway = $this->paymentGatewayManager->forPaymentMethod($paymentMethod);
            $result = $gateway->charge($session);

            return DB::transaction(function () use ($user, $validated, $selectedAddress, $cartLines, $paymentMethod, $session, $result, $request): array {
                $session->update([
                    'status' => CheckoutSession::STATUS_PAID,
                    'gateway_reference' => $result['reference'] ?? $session->uuid,
                    'gateway_response' => $result,
                    'paid_at' => now(),
                ]);

                Transaction::query()->create([
                    'checkout_session_id' => $session->id,
                    'user_id' => $user->id,
                    'reference' => (string) ($result['reference'] ?? $session->uuid),
                    'method' => $paymentMethod->code,
                    'status' => 'successful',
                    'amount' => $session->amount,
                    'currency' => $session->currency,
                    'meta' => $result,
                ]);

                $orders = $this->createOrdersFromCartLines(
                    user: $user,
                    validated: $validated,
                    selectedAddress: $selectedAddress,
                    cartLines: $cartLines,
                    paymentMethod: $paymentMethod,
                    request: $request,
                    checkoutSession: $session,
                    paymentStatus: Order::PAYMENT_STATUS_PAID,
                    paidAt: now(),
                );

                return [
                    'checkout_session' => $session->fresh(),
                    'orders' => $orders,
                ];
            });
        } catch (PaymentGatewayException $exception) {
            $session->update([
                'status' => CheckoutSession::STATUS_FAILED,
                'gateway_response' => [
                    'message' => $exception->getMessage(),
                    'response' => $exception->response,
                ],
            ]);

            throw ValidationException::withMessages([
                'payment' => $exception->getMessage(),
            ]);
        }
    }

    public function sessionForUser(User $user, string $uuid): CheckoutSession
    {
        return CheckoutSession::query()
            ->where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->with(['orders', 'paymentMethod'])
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: UserAddress, 1: Collection<int, array{store_product: \App\Models\StoreProduct, quantity: int, unit_price: float}>}
     */
    private function prepareCartLines(User $user, array $validated): array
    {
        $selectedAddress = $this->deliveryAddressService->selected($user);

        if (! $selectedAddress) {
            throw ValidationException::withMessages([
                'delivery_address' => 'Please select a delivery address before placing orders.',
            ]);
        }

        if ($selectedAddress->latitude === null || $selectedAddress->longitude === null) {
            throw ValidationException::withMessages([
                'delivery_address' => 'Please set your delivery location on the map before placing orders.',
            ]);
        }

        $coverage = $this->deliveryZoneService->coverageForPoint(
            (float) $selectedAddress->latitude,
            (float) $selectedAddress->longitude,
        );

        if (! $coverage['is_serviceable']) {
            throw ValidationException::withMessages([
                'delivery_address' => 'Delivery is not available at your selected address.',
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

        return [$selectedAddress, $cartLines];
    }

    /**
     * @param  Collection<int, array{store_product: \App\Models\StoreProduct, quantity: int, unit_price: float}>  $cartLines
     * @return Collection<int, Order>
     */
    private function createOrdersFromCartLines(
        User $user,
        array $validated,
        UserAddress $selectedAddress,
        Collection $cartLines,
        PaymentMethod $paymentMethod,
        ?Request $request = null,
        ?CheckoutSession $checkoutSession = null,
        ?string $paymentStatus = null,
        ?\Illuminate\Support\Carbon $paidAt = null,
    ): Collection {
        $ordersCreated = collect();

        DB::transaction(function () use (
            $user,
            $validated,
            $cartLines,
            $selectedAddress,
            $paymentMethod,
            $ordersCreated,
            $request,
            $checkoutSession,
            $paymentStatus,
            $paidAt,
        ): void {
            $groupedByStore = $cartLines->groupBy(fn (array $line): int => (int) $line['store_product']->store_id);

            foreach ($groupedByStore as $storeId => $storeLines) {
                $order = $this->createOrderForStore(
                    user: $user,
                    storeId: (int) $storeId,
                    storeLines: $storeLines,
                    validated: $validated,
                    selectedAddress: $selectedAddress,
                    paymentMethod: $paymentMethod,
                    request: $request,
                    checkoutSession: $checkoutSession,
                    paymentStatus: $paymentStatus,
                    paidAt: $paidAt,
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
        PaymentMethod $paymentMethod,
        ?Request $request,
        ?CheckoutSession $checkoutSession = null,
        ?string $paymentStatus = null,
        ?\Illuminate\Support\Carbon $paidAt = null,
    ): Order {
        $subtotal = round($storeLines->sum(fn (array $line): float => $line['quantity'] * $line['unit_price']), 2);
        $store = Store::query()->with('zones')->findOrFail($storeId);
        $deliveryZone = $this->deliveryZoneService->resolveZoneForStoreAtPoint(
            $store,
            (float) $selectedAddress->latitude,
            (float) $selectedAddress->longitude,
        );
        $deliveryFee = $deliveryZone !== null
            ? (float) $deliveryZone->base_delivery_fee
            : 2.50;
        $platformCommission = round($subtotal * 0.08, 2);
        $resolvedPaymentStatus = $paymentStatus ?? $this->paymentMethodService->initialPaymentStatus($paymentMethod);

        $order = Order::create([
            'user_id' => $user->id,
            'store_id' => $storeId,
            'delivery_zone_id' => $deliveryZone?->id,
            'checkout_session_id' => $checkoutSession?->id,
            'status' => Order::STATUS_PENDING,
            'payment_method' => $paymentMethod->code,
            'subtotal_amount' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'platform_commission' => $platformCommission,
            'total_amount' => round($subtotal + $deliveryFee, 2),
            'payment_status' => $resolvedPaymentStatus,
            'delivery_address_id' => $selectedAddress->id,
            'delivery_address' => $selectedAddress->address_line,
            'customer_phone' => $validated['customer_phone'] ?? $validated['customer_msisdn'] ?? null,
            'recipient_name' => ($validated['delivering_for_someone'] ?? false) ? ($validated['recipient_name'] ?? null) : null,
            'recipient_phone' => ($validated['delivering_for_someone'] ?? false) ? ($validated['recipient_phone'] ?? null) : null,
            'notes' => $validated['notes'] ?? null,
            'delivery_instructions' => $validated['delivery_instructions'],
            'placed_at' => now(),
            'paid_at' => $paidAt,
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

        if ($paymentMethod->isPrepay()) {
            $order->timeline()->create([
                'status' => Order::TIMELINE_PAYMENT_PAID,
                'note' => 'Payment confirmed via '.$paymentMethod->name,
                'changed_by' => $user->id,
            ]);
        }

        $order->timeline()->create([
            'status' => Order::STATUS_PENDING,
            'note' => 'Order placed by customer via checkout',
            'changed_by' => $user->id,
        ]);

        $this->activityLogger->log(
            event: 'order.created',
            subject: $order,
            userId: $user->id,
            metadata: [
                'store_id' => $storeId,
                'items_count' => $storeLines->count(),
                'payment_method' => $paymentMethod->code,
                'payment_status' => $resolvedPaymentStatus,
                'channel' => 'checkout',
            ],
            request: $request,
        );

        if ($resolvedPaymentStatus === Order::PAYMENT_STATUS_PAID || $paymentMethod->isOnDelivery()) {
            $this->orderWorkflowService->broadcastToRiders($order);
        }

        if ($checkoutSession !== null) {
            Transaction::query()
                ->where('checkout_session_id', $checkoutSession->id)
                ->whereNull('order_id')
                ->limit(1)
                ->update(['order_id' => $order->id]);
        }

        return $order;
    }

    /**
     * @param  array<string, mixed>  $cart
     */
    private function estimateTotal(User $user, ?UserAddress $selectedAddress, array $cart): float
    {
        $storeCount = collect($cart['stores'])->count();
        $deliveryFeePerStore = (float) ($cart['delivery_fee_per_store'] ?? 2.50);
        $fallback = round((float) $cart['subtotal'] + ($storeCount * $deliveryFeePerStore), 2);

        if ($selectedAddress === null) {
            return $fallback;
        }

        try {
            [, $cartLines] = $this->prepareCartLines($user, [
                'store_scope' => 'all',
                'selected_store_id' => null,
            ]);

            return $this->estimateTotalFromLines($selectedAddress, $cartLines);
        } catch (ValidationException) {
            return $fallback;
        }
    }

    /**
     * @param  Collection<int, array{store_product: \App\Models\StoreProduct, quantity: int, unit_price: float}>  $cartLines
     */
    private function estimateTotalFromLines(UserAddress $selectedAddress, Collection $cartLines): float
    {
        $total = 0.0;

        foreach ($cartLines->groupBy(fn (array $line): int => (int) $line['store_product']->store_id) as $storeId => $storeLines) {
            $subtotal = round($storeLines->sum(fn (array $line): float => $line['quantity'] * $line['unit_price']), 2);
            $store = Store::query()->with('zones')->find($storeId);
            $deliveryZone = $store
                ? $this->deliveryZoneService->resolveZoneForStoreAtPoint($store, (float) $selectedAddress->latitude, (float) $selectedAddress->longitude)
                : null;
            $deliveryFee = $deliveryZone !== null ? (float) $deliveryZone->base_delivery_fee : 2.50;
            $total += $subtotal + $deliveryFee;
        }

        return round($total, 2);
    }

    /**
     * @param  Collection<int, array{store_product: \App\Models\StoreProduct, quantity: int, unit_price: float}>  $cartLines
     * @return list<array<string, mixed>>
     */
    private function serializeCartLines(Collection $cartLines): array
    {
        return $cartLines->map(fn (array $line): array => [
            'store_product_id' => $line['store_product']->id,
            'store_id' => $line['store_product']->store_id,
            'product_id' => $line['store_product']->product_id,
            'quantity' => $line['quantity'],
            'unit_price' => $line['unit_price'],
        ])->values()->all();
    }
}
