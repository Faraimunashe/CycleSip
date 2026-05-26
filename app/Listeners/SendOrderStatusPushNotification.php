<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Models\User;
use App\Services\ExpoPushService;

class SendOrderStatusPushNotification
{
    public function __construct(
        private readonly ExpoPushService $expoPushService,
    ) {
    }

    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order->loadMissing(['user', 'rider', 'store']);
        $statusLabel = str_replace('_', ' ', $event->toStatus);
        $title = 'Order #'.$order->id.' updated';
        $body = 'Status is now '.$statusLabel.'.';

        if ($order->user instanceof User) {
            $this->expoPushService->sendToUser(
                user: $order->user,
                title: $title,
                body: $body,
                data: [
                    'type' => 'order_status',
                    'order_id' => $order->id,
                    'status' => $event->toStatus,
                    'role' => 'customer',
                ],
            );
        }

        if ($order->rider_id !== null && $order->rider instanceof User && $order->rider_id !== $event->changedBy) {
            $this->expoPushService->sendToUser(
                user: $order->rider,
                title: $title,
                body: $body,
                data: [
                    'type' => 'order_status',
                    'order_id' => $order->id,
                    'status' => $event->toStatus,
                    'role' => 'rider',
                ],
            );
        }
    }
}
