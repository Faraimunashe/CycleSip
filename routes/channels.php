<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('ops.orders', fn ($user) => $user->can('manage-orders'));
Broadcast::channel('orders.{orderId}', fn ($user, int $orderId) => $user->can('manage-orders') || $user->orders()->whereKey($orderId)->exists());
Broadcast::channel('ops.riders', fn ($user) => $user->can('manage-riders'));
