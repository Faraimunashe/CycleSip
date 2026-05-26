<?php

namespace App\Listeners;

use App\Events\OrderAvailableForRiders;
use App\Models\PushToken;
use App\Models\RiderProfile;
use App\Services\ExpoPushService;

class SendOrderAvailablePushNotification
{
    public function __construct(
        private readonly ExpoPushService $expoPushService,
    ) {
    }

    public function handle(OrderAvailableForRiders $event): void
    {
        $order = $event->order->loadMissing(['store']);
        $storeName = $order->store?->name ?? 'Store';
        $title = 'New delivery available';
        $body = "Order #{$order->id} from {$storeName} is ready to accept.";

        $riderIds = RiderProfile::query()
            ->where('approval_status', 'approved')
            ->where('is_online', true)
            ->pluck('user_id');

        if ($riderIds->isEmpty()) {
            return;
        }

        $tokens = PushToken::query()
            ->whereIn('user_id', $riderIds)
            ->pluck('token');

        $this->expoPushService->sendToTokens(
            tokens: $tokens,
            title: $title,
            body: $body,
            data: [
                'type' => 'order_available',
                'order_id' => $order->id,
                'role' => 'rider',
            ],
        );
    }
}
