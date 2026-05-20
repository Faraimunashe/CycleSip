<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage-orders') || $user->hasRole('customer');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasPermission('manage-orders')
            || $order->user_id === $user->id
            || $order->rider_id === $user->id;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasPermission('manage-orders')
            || ($user->hasRole('rider') && $order->rider_id === $user->id);
    }
}
