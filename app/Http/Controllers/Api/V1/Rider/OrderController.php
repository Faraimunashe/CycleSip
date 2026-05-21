<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Mobile\RiderOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private readonly RiderOrderService $riderOrderService,
    ) {
    }

    public function available(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->whereNull('rider_id')
            ->where('status', Order::STATUS_BROADCAST_TO_RIDERS)
            ->with(['store', 'user', 'orderRating.user', 'riderRating.user'])
            ->latest()
            ->paginate((int) $request->integer('per_page', 12));

        return $this->ok([
            'orders' => collect($orders->items())
                ->map(fn (Order $order): array => $this->riderOrderService->toListArray($order))
                ->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('rider_id', $request->user()->id)
            ->with(['store', 'user', 'orderRating.user', 'riderRating.user'])
            ->latest()
            ->paginate((int) $request->integer('per_page', 15));

        return $this->ok([
            'orders' => collect($orders->items())
                ->map(fn (Order $order): array => $this->riderOrderService->toListArray($order))
                ->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->riderOrderService->ensureCanAccess($request->user(), $order);

        $order->load(['user', 'store', 'rider', 'zone', 'items.product', 'timeline.changedBy', 'orderRating.user', 'riderRating.user']);

        $orderPayload = OrderResource::make($order)->resolve();
        $orderPayload['next_status_options'] = $this->riderOrderService->nextStatusOptions($order);
        $orderPayload['can_accept'] = $this->riderOrderService->isAvailableForAcceptance($order);
        $orderPayload['can_update_status'] = (int) $order->rider_id === (int) $request->user()->id;
        $orderPayload['order_rating'] = $this->riderOrderService->ratingDetails(
            rating: $order->orderRating?->rating,
            comment: $order->orderRating?->comment,
            reviewerName: $order->orderRating?->user?->name,
        );
        $orderPayload['rider_rating'] = $this->riderOrderService->ratingDetails(
            rating: $order->riderRating?->rating,
            comment: $order->riderRating?->comment,
            reviewerName: $order->riderRating?->user?->name,
        );

        return $this->ok(['order' => $orderPayload]);
    }

    public function accept(Request $request, Order $order): JsonResponse
    {
        $order = $this->riderOrderService->accept($request->user(), $order);

        return $this->ok([
            'order' => OrderResource::make($order)->resolve(),
        ], 'Order accepted successfully.');
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $order = $this->riderOrderService->updateStatus(
            rider: $request->user(),
            order: $order,
            status: $validated['status'],
        );

        return $this->ok([
            'order' => OrderResource::make($order)->resolve(),
        ], 'Order status updated successfully.');
    }
}
