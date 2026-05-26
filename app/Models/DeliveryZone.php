<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'center_latitude',
        'center_longitude',
        'radius_km',
        'base_delivery_fee',
        'distance_surcharge_per_km',
        'estimated_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'center_latitude' => 'float',
            'center_longitude' => 'float',
            'radius_km' => 'float',
            'base_delivery_fee' => 'float',
            'distance_surcharge_per_km' => 'float',
            'estimated_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Store, $this>
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class);
    }

    /**
     * @return BelongsToMany<RiderProfile, $this>
     */
    public function riderProfiles(): BelongsToMany
    {
        return $this->belongsToMany(RiderProfile::class, 'delivery_zone_rider');
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
