<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public string $fromStatus,
        public string $toStatus,
        public ?int $changedBy,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ops.orders'),
            new PrivateChannel("orders.{$this->order->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.status.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'from_status' => $this->fromStatus,
            'to_status' => $this->toStatus,
            'changed_by' => $this->changedBy,
            'updated_at' => optional($this->order->updated_at)?->toIso8601String(),
        ];
    }
}
