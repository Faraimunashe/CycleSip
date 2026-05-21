<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderRating;
use App\Models\RiderRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with(['store', 'items.product', 'orderRating', 'riderRating'])
            ->latest()
            ->get()
            ->map(fn (Order $order): array => $this->toSummaryArray($order))
            ->values();

        return $this->ok(['orders' => $orders]);
    }

    public function show(Request $request, Order $order): JsonResponse
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

        return $this->ok(['order' => $this->toDetailArray($order)]);
    }

    public function rateOrder(Request $request, Order $order): JsonResponse
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

        return $this->ok(null, 'Order rating saved.');
    }

    public function rateRider(Request $request, Order $order): JsonResponse
    {
        $this->ensureOrderBelongsToUser($request, $order);
        $this->ensureOrderCanBeRated($order);

        if ($order->rider_id === null) {
            return $this->error('This order has no assigned rider yet.', 422);
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

        return $this->ok(null, 'Rider rating saved.');
    }

    /**
     * @return array<string, mixed>
     */
    private function toSummaryArray(Order $order): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'subtotal_amount' => (float) $order->subtotal_amount,
            'delivery_fee' => (float) $order->delivery_fee,
            'total_amount' => (float) $order->total_amount,
            'delivery_address' => $order->delivery_address,
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toDetailArray(Order $order): array
    {
        return [
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
        ];
    }

    private function ensureOrderBelongsToUser(Request $request, Order $order): void
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403, 'You are not allowed to access this order.');
    }

    private function ensureOrderCanBeRated(Order $order): void
    {
        abort_unless($this->isRateableStatus($order), 422, 'Order can only be rated when delivered or completed.');
    }

    private function isRateableStatus(Order $order): bool
    {
        return in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true);
    }
}
