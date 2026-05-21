<?php

namespace App\Services\Mobile;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Collection;

class DeliveryAddressService
{
    /**
     * @return Collection<int, UserAddress>
     */
    public function list(User $user): Collection
    {
        return $user->addresses()->get();
    }

    public function selected(User $user): ?UserAddress
    {
        if ($user->selected_delivery_address_id === null) {
            return null;
        }

        return $user->addresses()
            ->where('id', $user->selected_delivery_address_id)
            ->first();
    }

    public function select(User $user, UserAddress $address): UserAddress
    {
        abort_unless((int) $address->user_id === (int) $user->id, 403, 'You cannot select this address.');

        $user->update(['selected_delivery_address_id' => $address->id]);

        return $address->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): UserAddress
    {
        if (($data['is_default'] ?? false) === true) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($data);

        if ($user->addresses()->count() === 1) {
            $address->update(['is_default' => true]);
        }

        $user->update(['selected_delivery_address_id' => $address->id]);

        return $address->fresh();
    }
}
