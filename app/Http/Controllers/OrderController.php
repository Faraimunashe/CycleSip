<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\StoreProduct;
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
    public function __construct(
        private readonly OrderWorkflowService $orderWorkflowService,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function index(Request $request): Response
    {
        $orders = $request->user()
            ->orders()
            ->with(['store', 'items.product'])
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
                'notes' => $order->notes,
                'placed_at' => optional($order->placed_at)?->toIso8601String(),
                'created_at' => $order->created_at->toIso8601String(),
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
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

        DB::transaction(function () use ($request, $validated, $lineItems): void {
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
                'delivery_address' => $validated['delivery_address'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'notes' => $validated['notes'] ?? null,
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
}
