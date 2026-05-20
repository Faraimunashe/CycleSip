<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class RiderDashboardController extends Controller
{
    /**
     * @var list<string>
     */
    private const RIDER_MANAGEABLE_STATUSES = [
        Order::STATUS_EN_ROUTE_TO_STORE,
        Order::STATUS_VERIFYING_STOCK,
        Order::STATUS_ADJUSTED,
        Order::STATUS_COLLECTING_ITEMS,
        Order::STATUS_EN_ROUTE_TO_CUSTOMER,
        Order::STATUS_DELIVERED,
        Order::STATUS_COMPLETED,
    ];

    public function __construct(
        private readonly OrderWorkflowService $orderWorkflowService,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->ensureRider($request);

        $rider = $request->user();

        $assignedOrders = Order::query()
            ->where('rider_id', $rider->id)
            ->with(['store', 'user', 'orderRating.user', 'riderRating.user'])
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn (Order $order): array => $this->toOrderListArray($order))
            ->values();

        return Inertia::render('Rider/Dashboard', [
            'metrics' => [
                'available_orders' => Order::query()
                    ->whereNull('rider_id')
                    ->where('status', Order::STATUS_BROADCAST_TO_RIDERS)
                    ->count(),
                'active_deliveries' => $assignedOrders->whereIn('status', [
                    Order::STATUS_ACCEPTED_BY_RIDER,
                    Order::STATUS_EN_ROUTE_TO_STORE,
                    Order::STATUS_VERIFYING_STOCK,
                    Order::STATUS_COLLECTING_ITEMS,
                    Order::STATUS_EN_ROUTE_TO_CUSTOMER,
                ])->count(),
                'completed_today' => Order::query()
                    ->where('rider_id', $rider->id)
                    ->whereDate('completed_at', today())
                    ->count(),
                'worked_orders' => Order::query()
                    ->where('rider_id', $rider->id)
                    ->count(),
            ],
            'assignedOrders' => $assignedOrders,
        ]);
    }

    public function available(Request $request): Response
    {
        $this->ensureRider($request);

        $orders = Order::query()
            ->whereNull('rider_id')
            ->where('status', Order::STATUS_BROADCAST_TO_RIDERS)
            ->with(['store', 'user', 'orderRating.user', 'riderRating.user'])
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Order $order): array => $this->toOrderListArray($order));

        return Inertia::render('Rider/AvailableOrders', [
            'orders' => $orders,
        ]);
    }

    public function history(Request $request): Response
    {
        $this->ensureRider($request);

        $orders = Order::query()
            ->where('rider_id', $request->user()->id)
            ->with(['store', 'user', 'orderRating.user', 'riderRating.user'])
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Order $order): array => $this->toOrderListArray($order));

        return Inertia::render('Rider/OrderHistory', [
            'orders' => $orders,
        ]);
    }

    public function accept(Request $request, Order $order): RedirectResponse
    {
        $this->ensureRider($request);

        try {
            DB::transaction(function () use ($request, $order): void {
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedOrder->rider_id !== null) {
                    throw ValidationException::withMessages([
                        'order' => 'This order has already been accepted by another rider.',
                    ]);
                }

                if ($lockedOrder->status !== Order::STATUS_BROADCAST_TO_RIDERS) {
                    throw ValidationException::withMessages([
                        'order' => 'This order is no longer available for acceptance.',
                    ]);
                }

                $lockedOrder->update([
                    'rider_id' => $request->user()->id,
                ]);

                $this->orderWorkflowService->transition(
                    order: $lockedOrder,
                    toStatus: Order::STATUS_ACCEPTED_BY_RIDER,
                    userId: $request->user()->id,
                    note: 'Order accepted by rider',
                );
            });
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return to_route('rider.orders.show', $order)->with('success', 'Order accepted successfully.');
    }

    public function show(Request $request, Order $order): Response
    {
        $this->ensureRider($request);

        $this->ensureRiderCanAccessOrder($request, $order);

        $order->load(['user', 'store', 'rider', 'zone', 'items.product', 'timeline.changedBy', 'orderRating.user', 'riderRating.user']);

        $orderPayload = OrderResource::make($order)->resolve();
        $orderPayload['next_status_options'] = $this->nextRiderStatusOptions($order);
        $orderPayload['can_accept'] = $this->isOrderAvailableForAcceptance($order);
        $orderPayload['can_update_status'] = (int) $order->rider_id === (int) $request->user()->id;
        $orderPayload['order_rating'] = $this->ratingDetails(
            rating: $order->orderRating?->rating,
            comment: $order->orderRating?->comment,
            reviewerName: $order->orderRating?->user?->name,
        );
        $orderPayload['rider_rating'] = $this->ratingDetails(
            rating: $order->riderRating?->rating,
            comment: $order->riderRating?->comment,
            reviewerName: $order->riderRating?->user?->name,
        );

        return Inertia::render('Rider/OrderShow', [
            'order' => $orderPayload,
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $this->ensureRider($request);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', self::RIDER_MANAGEABLE_STATUSES)],
        ]);

        try {
            DB::transaction(function () use ($request, $order, $validated): void {
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $lockedOrder->rider_id !== (int) $request->user()->id) {
                    throw ValidationException::withMessages([
                        'order' => 'You can only update orders assigned to you.',
                    ]);
                }

                $this->orderWorkflowService->transition(
                    order: $lockedOrder,
                    toStatus: $validated['status'],
                    userId: $request->user()->id,
                    note: 'Order status updated by rider',
                );
            });
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'status' => 'That status update is not allowed from the current order state.',
            ]);
        }

        return back()->with('success', 'Order status updated successfully.');
    }

    private function ensureRider(Request $request): void
    {
        abort_unless($request->user()?->hasRole('rider'), 403, 'Only riders can access this area.');
    }

    private function ensureRiderCanAccessOrder(Request $request, Order $order): void
    {
        $userId = (int) $request->user()->id;
        $isAssignedToRider = (int) $order->rider_id === $userId;
        $isAvailableOrder = $this->isOrderAvailableForAcceptance($order);

        abort_unless($isAssignedToRider || $isAvailableOrder, 403, 'You are not allowed to view this order.');
    }

    private function isOrderAvailableForAcceptance(Order $order): bool
    {
        return $order->rider_id === null && $order->status === Order::STATUS_BROADCAST_TO_RIDERS;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function nextRiderStatusOptions(Order $order): array
    {
        $allowed = $this->orderWorkflowService->allowedTransitionsFrom($order->status);
        $available = array_values(array_filter(
            $allowed,
            fn (string $status): bool => in_array($status, self::RIDER_MANAGEABLE_STATUSES, true)
        ));

        return array_map(
            fn (string $status): array => [
                'value' => $status,
                'label' => str($status)->replace('_', ' ')->title()->toString(),
            ],
            $available
        );
    }

    /**
     * @return array{
     *   id: int,
     *   status: string,
     *   store_name: ?string,
     *   customer_name: ?string,
     *   delivery_address: ?string,
     *   customer_phone: ?string,
     *   total_amount: float,
     *   placed_at: ?string,
     *   completed_at: ?string,
     *   order_rating: ?int,
     *   rider_rating: ?int,
     *   rating_has_comment: bool,
     *   next_status_options: list<array{value: string, label: string}>
     * }
     */
    private function toOrderListArray(Order $order): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status,
            'store_name' => $order->store?->name,
            'customer_name' => $order->user?->name,
            'delivery_address' => $order->delivery_address,
            'customer_phone' => $order->customer_phone,
            'total_amount' => (float) $order->total_amount,
            'placed_at' => optional($order->placed_at)?->toIso8601String(),
            'completed_at' => optional($order->completed_at)?->toIso8601String(),
            'order_rating' => $order->orderRating?->rating !== null ? (int) $order->orderRating->rating : null,
            'rider_rating' => $order->riderRating?->rating !== null ? (int) $order->riderRating->rating : null,
            'rating_has_comment' => filled($order->riderRating?->comment) || filled($order->orderRating?->comment),
            'next_status_options' => $this->nextRiderStatusOptions($order),
        ];
    }

    /**
     * @return array{rating: ?int, comment: ?string, reviewer_name: ?string}|null
     */
    private function ratingDetails(?int $rating, ?string $comment, ?string $reviewerName): ?array
    {
        if ($rating === null) {
            return null;
        }

        return [
            'rating' => $rating,
            'comment' => $comment,
            'reviewer_name' => $reviewerName,
        ];
    }
}
