<?php

namespace App\Jobs;

use App\Events\OrderAvailableForRiders;
use App\Models\Order;
use Illuminate\Foundation\Queue\Queueable;

class BroadcastOrderToRidersJob
{
    use Queueable;

    public function __construct(public int $orderId)
    {
    }

    public function handle(): void
    {
        $order = Order::query()->with(['store', 'user'])->find($this->orderId);

        if (! $order || $order->status !== Order::STATUS_BROADCAST_TO_RIDERS) {
            return;
        }

        event(new OrderAvailableForRiders($order));
    }
}
