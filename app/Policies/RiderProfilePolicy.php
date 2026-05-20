<?php

namespace App\Policies;

use App\Models\RiderProfile;
use App\Models\User;

class RiderProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage-riders') || $user->hasRole('operations-manager');
    }

    public function view(User $user, RiderProfile $riderProfile): bool
    {
        return $user->hasPermission('manage-riders') || $riderProfile->user_id === $user->id;
    }

    public function approve(User $user, RiderProfile $riderProfile): bool
    {
        return $user->hasPermission('approve-riders');
    }
}
