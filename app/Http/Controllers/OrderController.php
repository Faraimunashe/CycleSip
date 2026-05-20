<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderRating;
use App\Models\RiderRating;
use App\Models\StoreProduct;
use App\Models\UserAddress;
use App\Services\ActivityLogger;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    private const CART_SESSION_KEY = 'cart.items';

    public function __construct(
        private readonly OrderWorkflowService $orderWorkflowService,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function index(Request $request): Response
    {
        $orders = $request->user()
            ->orders()
            ->with(['store', 'items.product', 'orderRating', 'riderRating'])
            ->latest()
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'subtotal_amount' => (float) $order->subtotal_amount,
                'delivery_fee' => (float) $order->delivery_fee,
                'total_amount' => (float) $order->total_amount,
                'delivery_address' => $order->delivery_address,
                'customer_phone' => $order->customer_phone,
                'recipient_name' => $order->recipient_name,
                'recipient_phone' => $order->recipient_phone,
                'delivery_instructions' => $order->delivery_instructions,
                'notes' => $order->notes,
                'placed_at' => optional($order->placed_at)?->toIso8601String(),
                'created_at' => $order->created_at->toIso8601String(),
                'can_rate' => $this->isRateableStatus($order),
                'has_order_rating' => $order->orderRating !== null,
                'has_rider_rating' => $order->riderRating !== null,
                'store' => [
                    'id' => $order->store->id,
                    'name' => $order->store->name,
                ],
                'items' => $order->items->map(fn ($item): array => [
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ])->values(),
            ])
            ->values();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $this->ensureOrderBelongsToUser($request, $order);

        $order->load([
            'store',
            'rider',
            'items.product',
            'timeline.changedBy',
            'orderRating',
            'riderRating.rider',
        ]);

        return Inertia::render('Orders/Show', [
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'subtotal_amount' => (float) $order->subtotal_amount,
                'delivery_fee' => (float) $order->delivery_fee,
                'platform_commission' => (float) $order->platform_commission,
                'total_amount' => (float) $order->total_amount,
                'delivery_address' => $order->delivery_address,
                'customer_phone' => $order->customer_phone,
                'recipient_name' => $order->recipient_name,
                'recipient_phone' => $order->recipient_phone,
                'delivery_instructions' => $order->delivery_instructions,
                'notes' => $order->notes,
                'placed_at' => optional($order->placed_at)?->toIso8601String(),
                'accepted_at' => optional($order->accepted_at)?->toIso8601String(),
                'delivered_at' => optional($order->delivered_at)?->toIso8601String(),
                'completed_at' => optional($order->completed_at)?->toIso8601String(),
                'store' => [
                    'id' => $order->store?->id,
                    'name' => $order->store?->name,
                    'address' => $order->store?->address,
                ],
                'rider' => [
                    'id' => $order->rider?->id,
                    'name' => $order->rider?->name,
                    'phone' => $order->rider?->phone,
                ],
                'items' => $order->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product?->name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ])->values(),
                'timeline' => $order->timeline->map(fn ($entry): array => [
                    'id' => $entry->id,
                    'status' => $entry->status,
                    'note' => $entry->note,
                    'changed_by' => $entry->changedBy?->name,
                    'created_at' => optional($entry->created_at)?->toIso8601String(),
                ])->values(),
                'order_rating' => $order->orderRating
                    ? [
                        'rating' => (int) $order->orderRating->rating,
                        'comment' => $order->orderRating->comment,
                    ]
                    : null,
                'rider_rating' => $order->riderRating
                    ? [
                        'rating' => (int) $order->riderRating->rating,
                        'comment' => $order->riderRating->comment,
                    ]
                    : null,
                'can_rate' => $this->isRateableStatus($order),
            ],
        ]);
    }

    public function rateOrder(Request $request, Order $order): RedirectResponse
    {
        $this->ensureOrderBelongsToUser($request, $order);
        $this->ensureOrderCanBeRated($order);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        OrderRating::query()->updateOrCreate(
            [
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
            ],
            [
                'rating' => (int) $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        return back()->with('success', 'Order rating saved.');
    }

    public function rateRider(Request $request, Order $order): RedirectResponse
    {
        $this->ensureOrderBelongsToUser($request, $order);
        $this->ensureOrderCanBeRated($order);

        if ($order->rider_id === null) {
            return back()->withErrors(['rating' => 'This order has no assigned rider yet.']);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        RiderRating::query()->updateOrCreate(
            [
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
            ],
            [
                'rider_id' => (int) $order->rider_id,
                'rating' => (int) $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        return back()->with('success', 'Rider rating saved.');
    }

    public function store(Request $request): RedirectResponse
    {
        $selectedAddress = $this->selectedDeliveryAddress($request);
        if (! $selectedAddress) {
            $request->session()->put('address_selection_required', true);

            return to_route('addresses.select')->with('error', 'Please choose a delivery address before placing orders.');
        }

        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'delivery_instructions' => ['required', 'string', 'max:500'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'recipient_phone' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.store_product_id' => ['required', 'integer', 'exists:store_products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'ecocash', 'innbucks', 'swipe'])],
        ]);

        $storeProducts = StoreProduct::query()
            ->with('product')
            ->where('store_id', $validated['store_id'])
            ->whereIn('id', collect($validated['items'])->pluck('store_product_id'))
            ->get()
            ->keyBy('id');

        if ($storeProducts->count() !== count($validated['items'])) {
            return back()->withErrors([
                'items' => 'One or more selected products are not available for the selected store.',
            ]);
        }

        $lineItems = collect($validated['items'])
            ->map(function (array $item) use ($storeProducts): array {
                $storeProduct = $storeProducts->get($item['store_product_id']);

                return [
                    'store_product' => $storeProduct,
                    'quantity' => (int) $item['quantity'],
                ];
            })
            ->filter(fn (array $item): bool => $item['quantity'] > 0)
            ->values();

        if ($lineItems->isEmpty()) {
            return back()->withErrors(['items' => 'Please add at least one item to your order.']);
        }

        /** @var Collection<int, string> $stockErrors */
        $stockErrors = $lineItems
            ->filter(fn (array $item): bool => $item['quantity'] > $item['store_product']->stock_quantity)
            ->map(fn (array $item): string => "{$item['store_product']->product->name} does not have enough stock.")
            ->values();

        if ($stockErrors->isNotEmpty()) {
            return back()->withErrors(['items' => $stockErrors->implode(' ')]);
        }

        DB::transaction(function () use ($request, $validated, $lineItems, $selectedAddress): void {
            $total = $lineItems->reduce(
                fn (float $carry, array $item): float => $carry + ($item['quantity'] * (float) $item['store_product']->price),
                0
            );

            $subtotal = round($total, 2);
            $deliveryFee = 2.50;
            $platformCommission = round($subtotal * 0.08, 2);

            $order = Order::create([
                'user_id' => $request->user()->id,
                'store_id' => $validated['store_id'],
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
                'recipient_name' => $validated['recipient_name'] ?? null,
                'recipient_phone' => $validated['recipient_phone'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'delivery_instructions' => $validated['delivery_instructions'],
                'placed_at' => now(),
            ]);

            foreach ($lineItems as $item) {
                $storeProduct = $item['store_product'];
                $quantity = $item['quantity'];
                $unitPrice = (float) $storeProduct->price;

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
                'note' => 'Order placed by customer',
                'changed_by' => $request->user()->id,
            ]);

            $this->activityLogger->log(
                event: 'order.created',
                subject: $order,
                userId: $request->user()->id,
                metadata: [
                    'store_id' => $validated['store_id'],
                    'items_count' => $lineItems->count(),
                    'payment_method' => $validated['payment_method'],
                ],
                request: $request
            );

            $this->orderWorkflowService->broadcastToRiders($order);
        });

        return to_route('orders.index')->with('success', 'Order placed successfully.');
    }

    public function checkout(Request $request): Response|RedirectResponse
    {
        $selectedAddress = $this->selectedDeliveryAddress($request);
        if (! $selectedAddress) {
            $request->session()->put('address_selection_required', true);

            return to_route('addresses.select')->with('error', 'Please choose a delivery address before checkout.');
        }

        return Inertia::render('Orders/Checkout', [
            'cart' => $this->cartPayload($request),
            'paymentMethods' => ['cash', 'ecocash', 'innbucks', 'swipe'],
            'selectedAddress' => [
                'id' => $selectedAddress->id,
                'label' => $selectedAddress->label,
                'address_line' => $selectedAddress->address_line,
                'latitude' => $selectedAddress->latitude,
                'longitude' => $selectedAddress->longitude,
            ],
        ]);
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_product_id' => ['required', 'integer', 'exists:store_products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $storeProduct = StoreProduct::query()->findOrFail($validated['store_product_id']);

        if (! $storeProduct->is_available || $storeProduct->stock_quantity < 1) {
            return back()->withErrors(['cart' => 'This product is currently unavailable.']);
        }

        $cartItems = $this->getCartItemsMap($request);
        $existingQuantity = (int) ($cartItems[$storeProduct->id] ?? 0);
        $nextQuantity = min($existingQuantity + (int) $validated['quantity'], max(1, (int) $storeProduct->stock_quantity));
        $cartItems[$storeProduct->id] = $nextQuantity;

        $this->persistCartItemsMap($request, $cartItems);

        return back()->with('success', 'Item added to cart.');
    }

    public function updateCartItem(Request $request, StoreProduct $storeProduct): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $cartItems = $this->getCartItemsMap($request);

        if ((int) $validated['quantity'] === 0) {
            unset($cartItems[$storeProduct->id]);
        } else {
            $cartItems[$storeProduct->id] = min((int) $validated['quantity'], max(1, (int) $storeProduct->stock_quantity));
        }

        $this->persistCartItemsMap($request, $cartItems);

        return back()->with('success', 'Cart updated.');
    }

    public function removeCartItem(Request $request, StoreProduct $storeProduct): RedirectResponse
    {
        $cartItems = $this->getCartItemsMap($request);
        unset($cartItems[$storeProduct->id]);
        $this->persistCartItemsMap($request, $cartItems);

        return back()->with('success', 'Item removed from cart.');
    }

    public function clearCart(Request $request): RedirectResponse
    {
        $request->session()->forget(self::CART_SESSION_KEY);

        return back()->with('success', 'Cart cleared.');
    }

    public function processCheckout(Request $request): RedirectResponse
    {
        $selectedAddress = $this->selectedDeliveryAddress($request);
        if (! $selectedAddress) {
            $request->session()->put('address_selection_required', true);

            return to_route('addresses.select')->with('error', 'Please choose a delivery address before placing orders.');
        }

        $validated = $request->validate([
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'delivery_instructions' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in(['cash', 'ecocash', 'innbucks', 'swipe'])],
            'store_scope' => ['required', Rule::in(['all', 'single'])],
            'selected_store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'delivering_for_someone' => ['nullable', 'boolean'],
            'recipient_name' => ['nullable', 'string', 'max:120', 'required_if:delivering_for_someone,1'],
            'recipient_phone' => ['nullable', 'string', 'max:32', 'required_if:delivering_for_someone,1'],
        ]);

        $cartLines = $this->cartLines($request);

        if ($cartLines->isEmpty()) {
            return back()->withErrors(['cart' => 'Your cart is empty.']);
        }

        if ($validated['store_scope'] === 'single') {
            if (! $validated['selected_store_id']) {
                return back()->withErrors(['selected_store_id' => 'Please select a store.']);
            }

            $cartLines = $cartLines
                ->filter(fn (array $line): bool => $line['store_product']->store_id === (int) $validated['selected_store_id'])
                ->values();

            if ($cartLines->isEmpty()) {
                return back()->withErrors(['selected_store_id' => 'No cart items found for the selected store.']);
            }
        }

        /** @var Collection<int, string> $stockErrors */
        $stockErrors = $cartLines
            ->filter(fn (array $line): bool => ! $line['store_product']->is_available || $line['quantity'] > (int) $line['store_product']->stock_quantity)
            ->map(fn (array $line): string => "{$line['store_product']->product->name} is out of stock or has limited quantity.")
            ->values();

        if ($stockErrors->isNotEmpty()) {
            return back()->withErrors(['cart' => $stockErrors->implode(' ')]);
        }

        $ordersCreated = collect();

        DB::transaction(function () use ($request, $validated, $cartLines, $ordersCreated, $selectedAddress): void {
            $groupedByStore = $cartLines->groupBy(fn (array $line): int => (int) $line['store_product']->store_id);

            foreach ($groupedByStore as $storeId => $storeLines) {
                $subtotal = round($storeLines->sum(fn (array $line): float => $line['quantity'] * $line['unit_price']), 2);
                $deliveryFee = 2.50;
                $platformCommission = round($subtotal * 0.08, 2);

                $order = Order::create([
                    'user_id' => $request->user()->id,
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
                    'note' => 'Order placed by customer via checkout',
                    'changed_by' => $request->user()->id,
                ]);

                $this->activityLogger->log(
                    event: 'order.created',
                    subject: $order,
                    userId: $request->user()->id,
                    metadata: [
                        'store_id' => $storeId,
                        'items_count' => $storeLines->count(),
                        'payment_method' => $validated['payment_method'],
                    ],
                    request: $request
                );

                $this->orderWorkflowService->broadcastToRiders($order);
                $ordersCreated->push($order->id);
            }
        });

        $cartItems = $this->getCartItemsMap($request);
        foreach ($cartLines as $line) {
            unset($cartItems[$line['store_product']->id]);
        }
        $this->persistCartItemsMap($request, $cartItems);

        return to_route('orders.index')->with('success', "Checkout completed. {$ordersCreated->count()} order(s) placed.");
    }

    /**
     * @return array<int, int>
     */
    private function getCartItemsMap(Request $request): array
    {
        $raw = $request->session()->get(self::CART_SESSION_KEY, []);

        if (! is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->mapWithKeys(fn ($quantity, $storeProductId): array => [(int) $storeProductId => max(0, (int) $quantity)])
            ->filter(fn (int $quantity): bool => $quantity > 0)
            ->all();
    }

    /**
     * @param  array<int, int>  $cartItems
     */
    private function persistCartItemsMap(Request $request, array $cartItems): void
    {
        $request->session()->put(self::CART_SESSION_KEY, $cartItems);
    }

    /**
     * @return Collection<int, array{store_product: StoreProduct, quantity: int, unit_price: float}>
     */
    private function cartLines(Request $request): Collection
    {
        $cartItems = $this->getCartItemsMap($request);
        if ($cartItems === []) {
            return collect();
        }

        $storeProducts = StoreProduct::query()
            ->with(['store', 'product'])
            ->whereIn('id', array_keys($cartItems))
            ->get()
            ->keyBy('id');

        return collect($cartItems)
            ->map(function (int $quantity, int $storeProductId) use ($storeProducts): ?array {
                $storeProduct = $storeProducts->get($storeProductId);

                if (! $storeProduct) {
                    return null;
                }

                $unitPrice = $storeProduct->promotion_price !== null
                    ? (float) $storeProduct->promotion_price
                    : (float) $storeProduct->price;

                return [
                    'store_product' => $storeProduct,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function cartPayload(Request $request): array
    {
        $lines = $this->cartLines($request);

        $stores = $lines
            ->groupBy(fn (array $line): int => (int) $line['store_product']->store_id)
            ->map(function (Collection $storeLines, int $storeId): array {
                /** @var StoreProduct $firstStoreProduct */
                $firstStoreProduct = $storeLines->first()['store_product'];

                return [
                    'id' => $storeId,
                    'name' => $firstStoreProduct->store?->name,
                    'address' => $firstStoreProduct->store?->address,
                    'items' => $storeLines->map(fn (array $line): array => [
                        'store_product_id' => $line['store_product']->id,
                        'product_name' => $line['store_product']->product?->name,
                        'image_url' => $line['store_product']->product?->image_url,
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'line_total' => round($line['quantity'] * $line['unit_price'], 2),
                        'stock_quantity' => (int) $line['store_product']->stock_quantity,
                        'is_available' => (bool) $line['store_product']->is_available,
                    ])->values(),
                ];
            })
            ->values();

        return [
            'line_count' => $lines->count(),
            'item_count' => $lines->sum('quantity'),
            'subtotal' => round($lines->sum(fn (array $line): float => $line['quantity'] * $line['unit_price']), 2),
            'stores' => $stores,
        ];
    }

    private function ensureOrderBelongsToUser(Request $request, Order $order): void
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403, 'You are not allowed to access this order.');
    }

    private function ensureOrderCanBeRated(Order $order): void
    {
        if (! $this->isRateableStatus($order)) {
            abort(422, 'Order can only be rated when delivered or completed.');
        }
    }

    private function isRateableStatus(Order $order): bool
    {
        return in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true);
    }

    private function selectedDeliveryAddress(Request $request): ?UserAddress
    {
        $selectedAddressId = $request->session()->get('selected_delivery_address_id');
        if (! $selectedAddressId || ! $request->user()) {
            return null;
        }

        return $request->user()
            ->addresses()
            ->where('id', $selectedAddressId)
            ->first();
    }
}
