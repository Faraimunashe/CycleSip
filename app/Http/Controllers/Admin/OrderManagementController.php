<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustOrderItemsRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\StoreProduct;
use App\Repositories\OrderRepository;
use App\Services\ActivityLogger;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrderManagementController extends Controller
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderWorkflowService $orderWorkflowService,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function index(Request $request): Response
    {
        $orders = $this->orderRepository->paginatedForOps(
            filters: $request->only(['search', 'status', 'payment_method']),
            perPage: 12
        );

        $ordersPayload = $orders->toArray();
        $ordersPayload['data'] = OrderResource::collection($orders->getCollection())->resolve();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $ordersPayload,
            'statuses' => Order::ALLOWED_STATUSES,
            'filters' => $request->only(['search', 'status', 'payment_method']),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();

        $this->orderWorkflowService->transition(
            order: $order,
            toStatus: $validated['status'],
            userId: $request->user()->id,
            note: $validated['note'] ?? null,
        );

        return to_route('admin.orders.show', $order)->with('success', 'Order status updated.');
    }

    public function show(Order $order): Response
    {
        $order->load(['user', 'store', 'rider', 'zone', 'items.product', 'timeline.changedBy']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => OrderResource::make($order)->resolve(),
        ]);
    }

    public function edit(Order $order): Response
    {
        $order->load(['user', 'store', 'rider', 'zone', 'items.product', 'timeline.changedBy']);

        return Inertia::render('Admin/Orders/Edit', [
            'order' => OrderResource::make($order)->resolve(),
            'statuses' => Order::ALLOWED_STATUSES,
        ]);
    }

    public function adjustItems(AdjustOrderItemsRequest $request, Order $order): RedirectResponse
    {
        if (in_array($order->status, [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED], true)) {
            return back()->withErrors([
                'items' => 'Completed or cancelled orders cannot be adjusted.',
            ]);
        }

        $validatedItems = collect($request->validated('items'));
        $order->load('items.product');

        $orderItems = $order->items->keyBy('id');

        if ($validatedItems->contains(fn (array $item): bool => ! $orderItems->has((int) $item['item_id']))) {
            return back()->withErrors([
                'items' => 'One or more selected items do not belong to this order.',
            ]);
        }

        $hasInvalidIncrease = $validatedItems->contains(function (array $item) use ($orderItems): bool {
            $existing = $orderItems->get((int) $item['item_id']);

            return (int) $item['quantity'] > (int) $existing->quantity;
        });

        if ($hasInvalidIncrease) {
            return back()->withErrors([
                'items' => 'Item adjustments can only reduce quantity or remove items.',
            ]);
        }

        $remainingItemCount = $validatedItems->filter(fn (array $item): bool => (int) $item['quantity'] > 0)->count();
        if ($remainingItemCount === 0) {
            return back()->withErrors([
                'items' => 'An order must have at least one item after adjustment.',
            ]);
        }

        DB::transaction(function () use ($validatedItems, $order, $orderItems, $request): void {
            foreach ($validatedItems as $itemData) {
                $itemId = (int) $itemData['item_id'];
                $newQuantity = (int) $itemData['quantity'];
                $orderItem = $orderItems->get($itemId);
                $oldQuantity = (int) $orderItem->quantity;

                if ($newQuantity === $oldQuantity) {
                    continue;
                }

                $restoreBy = $oldQuantity - $newQuantity;
                if ($restoreBy > 0) {
                    StoreProduct::query()
                        ->where('store_id', $order->store_id)
                        ->where('product_id', $orderItem->product_id)
                        ->increment('stock_quantity', $restoreBy);
                }

                if ($newQuantity === 0) {
                    $orderItem->delete();
                    continue;
                }

                $orderItem->update([
                    'quantity' => $newQuantity,
                    'line_total' => round((float) $orderItem->unit_price * $newQuantity, 2),
                ]);
            }

            $order->load('items');
            $newSubtotal = round((float) $order->items->sum('line_total'), 2);
            $newCommission = round($newSubtotal * 0.08, 2);

            $order->update([
                'status' => Order::STATUS_ADJUSTED,
                'subtotal_amount' => $newSubtotal,
                'platform_commission' => $newCommission,
                'total_amount' => round($newSubtotal + (float) $order->delivery_fee, 2),
            ]);

            $order->timeline()->create([
                'status' => Order::STATUS_ADJUSTED,
                'note' => 'Order items adjusted by admin',
                'changed_by' => $request->user()->id,
            ]);

            $this->activityLogger->log(
                event: 'order.items_adjusted',
                subject: $order,
                userId: $request->user()->id,
                metadata: [
                    'order_id' => $order->id,
                    'items_adjusted' => $validatedItems->count(),
                ],
                request: $request
            );
        });

        return to_route('admin.orders.show', $order)->with('success', 'Order items adjusted successfully.');
    }
}
