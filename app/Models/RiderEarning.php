<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderEarning extends Model
{
    protected $fillable = [
        'rider_profile_id',
        'order_id',
        'gross_amount',
        'platform_fee',
        'net_amount',
        'status',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'settled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<RiderProfile, $this>
     */
    public function riderProfile(): BelongsTo
    {
        return $this->belongsTo(RiderProfile::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
