<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage-stores');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage-stores');
    }

    public function update(User $user, Store $store): bool
    {
        return $user->hasPermission('manage-stores');
    }
}
