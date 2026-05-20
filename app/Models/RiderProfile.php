<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiderProfile extends Model
{
    protected $fillable = [
        'user_id',
        'approval_status',
        'is_online',
        'vehicle_type',
        'bicycle_model',
        'license_number',
        'emergency_contact_name',
        'emergency_contact_phone',
        'acceptance_rate',
        'cancellation_rate',
        'completed_deliveries',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<RiderLocation, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(RiderLocation::class);
    }

    /**
     * @return HasMany<RiderDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(RiderDocument::class);
    }

    /**
     * @return HasMany<RiderEarning, $this>
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(RiderEarning::class);
    }

    /**
     * @return BelongsToMany<DeliveryZone, $this>
     */
    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryZone::class, 'delivery_zone_rider');
    }
}
