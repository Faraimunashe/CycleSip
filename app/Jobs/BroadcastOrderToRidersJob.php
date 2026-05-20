<?php

namespace App\Jobs;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BroadcastOrderToRidersJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId)
    {
    }

    public function handle(): void
    {
        $order = Order::query()->find($this->orderId);

        if (! $order) {
            return;
        }

        event(new OrderStatusChanged(
            order: $order,
            fromStatus: $order->getOriginal('status') ?? $order->status,
            toStatus: $order->status,
            changedBy: null,
        ));
    }
}
