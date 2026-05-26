<?php

use App\Models\Order;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('ops.orders', fn ($user) => $user->can('manage-orders'));
Broadcast::channel('orders.{orderId}', function ($user, int $orderId): bool {
    if ($user->can('manage-orders')) {
        return true;
    }

    if ($user->orders()->whereKey($orderId)->exists()) {
        return true;
    }

    return Order::query()
        ->whereKey($orderId)
        ->where('rider_id', $user->id)
        ->exists();
});
Broadcast::channel('ops.riders', fn ($user) => $user->can('manage-riders'));
Broadcast::channel('riders.marketplace', function ($user): bool {
    if (! $user->hasRole('rider')) {
        return false;
    }

    $profile = $user->riderProfile;

    return $profile !== null && $profile->approval_status === 'approved';
});
