<?php

namespace App\Services\Mobile;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class RiderOrderService
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

    public function accept(User $rider, Order $order): Order
    {
        DB::transaction(function () use ($rider, $order): void {
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
                'rider_id' => $rider->id,
            ]);

            $this->orderWorkflowService->transition(
                order: $lockedOrder,
                toStatus: Order::STATUS_ACCEPTED_BY_RIDER,
                userId: $rider->id,
                note: 'Order accepted by rider',
            );
        });

        return $order->fresh([
            'store',
            'user',
            'rider',
            'items.product',
            'timeline.changedBy',
            'orderRating.user',
            'riderRating.user',
        ]);
    }

    public function updateStatus(User $rider, Order $order, string $status): Order
    {
        if (! in_array($status, self::RIDER_MANAGEABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid status for rider update.',
            ]);
        }

        try {
            DB::transaction(function () use ($rider, $order, $status): void {
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $lockedOrder->rider_id !== (int) $rider->id) {
                    throw ValidationException::withMessages([
                        'order' => 'You can only update orders assigned to you.',
                    ]);
                }

                $this->orderWorkflowService->transition(
                    order: $lockedOrder,
                    toStatus: $status,
                    userId: $rider->id,
                    note: 'Order status updated by rider',
                );
            });
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'status' => 'That status update is not allowed from the current order state.',
            ]);
        }

        return $order->fresh([
            'store',
            'user',
            'rider',
            'items.product',
            'timeline.changedBy',
            'orderRating.user',
            'riderRating.user',
        ]);
    }

    public function ensureCanAccess(User $rider, Order $order): void
    {
        $isAssignedToRider = (int) $order->rider_id === (int) $rider->id;
        $isAvailableOrder = $this->isAvailableForAcceptance($order);

        abort_unless($isAssignedToRider || $isAvailableOrder, 403, 'You are not allowed to view this order.');
    }

    public function isAvailableForAcceptance(Order $order): bool
    {
        return $order->rider_id === null && $order->status === Order::STATUS_BROADCAST_TO_RIDERS;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function nextStatusOptions(Order $order): array
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
     * @return array<string, mixed>
     */
    public function toListArray(Order $order): array
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
            'next_status_options' => $this->nextStatusOptions($order),
        ];
    }

    /**
     * @return array{rating: ?int, comment: ?string, reviewer_name: ?string}|null
     */
    public function ratingDetails(?int $rating, ?string $comment, ?string $reviewerName): ?array
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
