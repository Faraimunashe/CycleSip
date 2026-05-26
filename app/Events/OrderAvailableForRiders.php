<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAvailableForRiders implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing(['store', 'user']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('riders.marketplace'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.available';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'store_id' => $this->order->store_id,
            'store_name' => $this->order->store?->name,
            'total_amount' => (float) $this->order->total_amount,
            'delivery_fee' => (float) $this->order->delivery_fee,
            'delivery_address' => $this->order->delivery_address,
            'customer_name' => $this->order->user?->name,
            'placed_at' => optional($this->order->placed_at)?->toIso8601String(),
        ];
    }
}
