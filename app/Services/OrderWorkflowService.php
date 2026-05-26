<?php

namespace App\Services;

use App\Events\OrderStatusChanged;
use App\Jobs\BroadcastOrderToRidersJob;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderWorkflowService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    /**
     * @var array<string, list<string>>
     */
    private array $transitions = [
        Order::STATUS_PENDING => [Order::STATUS_BROADCAST_TO_RIDERS, Order::STATUS_CANCELLED],
        Order::STATUS_BROADCAST_TO_RIDERS => [Order::STATUS_ACCEPTED_BY_RIDER, Order::STATUS_CANCELLED],
        Order::STATUS_ACCEPTED_BY_RIDER => [Order::STATUS_EN_ROUTE_TO_STORE, Order::STATUS_CANCELLED],
        Order::STATUS_EN_ROUTE_TO_STORE => [Order::STATUS_VERIFYING_STOCK, Order::STATUS_CANCELLED],
        Order::STATUS_VERIFYING_STOCK => [Order::STATUS_COLLECTING_ITEMS, Order::STATUS_ADJUSTED, Order::STATUS_CANCELLED],
        Order::STATUS_ADJUSTED => [Order::STATUS_COLLECTING_ITEMS, Order::STATUS_CANCELLED],
        Order::STATUS_COLLECTING_ITEMS => [Order::STATUS_EN_ROUTE_TO_CUSTOMER, Order::STATUS_CANCELLED],
        Order::STATUS_EN_ROUTE_TO_CUSTOMER => [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED],
        Order::STATUS_DELIVERED => [Order::STATUS_COMPLETED],
        Order::STATUS_COMPLETED => [],
        Order::STATUS_CANCELLED => [],
    ];

    /**
     * @return list<string>
     */
    public function allowedTransitionsFrom(string $status): array
    {
        return $this->transitions[$status] ?? [];
    }

    public function broadcastToRiders(Order $order): void
    {
        $this->transition($order, Order::STATUS_BROADCAST_TO_RIDERS, null, 'Order broadcast to riders');
        BroadcastOrderToRidersJob::dispatchSync($order->id);
    }

    public function transition(Order $order, string $toStatus, ?int $userId = null, ?string $note = null): Order
    {
        $fromStatus = $order->status;
        $allowed = $this->transitions[$fromStatus] ?? [];

        if (! in_array($toStatus, $allowed, true)) {
            throw new InvalidArgumentException("Invalid status transition from {$fromStatus} to {$toStatus}");
        }

        return DB::transaction(function () use ($order, $toStatus, $userId, $fromStatus, $note): Order {
            $order->update([
                'status' => $toStatus,
                'accepted_at' => $toStatus === Order::STATUS_ACCEPTED_BY_RIDER ? now() : $order->accepted_at,
                'delivered_at' => $toStatus === Order::STATUS_DELIVERED ? now() : $order->delivered_at,
                'completed_at' => $toStatus === Order::STATUS_COMPLETED ? now() : $order->completed_at,
                'cancelled_at' => $toStatus === Order::STATUS_CANCELLED ? now() : $order->cancelled_at,
            ]);

            $order->timeline()->create([
                'status' => $toStatus,
                'note' => $note,
                'changed_by' => $userId,
            ]);

            $this->activityLogger->log(
                event: 'order.status_changed',
                subject: $order,
                userId: $userId,
                metadata: [
                    'from' => $fromStatus,
                    'to' => $toStatus,
                    'note' => $note,
                ],
            );

            event(new OrderStatusChanged($order, $fromStatus, $toStatus, $userId));

            return $order->fresh(['timeline', 'user', 'store', 'rider']);
        });
    }
}
