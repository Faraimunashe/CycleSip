<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    public const TIMING_ON_DELIVERY = 'on_delivery';
    public const TIMING_PREPAY = 'prepay';

    protected $fillable = [
        'code',
        'name',
        'description',
        'timing',
        'gateway',
        'is_enabled',
        'requires_phone',
        'sort_order',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'requires_phone' => 'boolean',
            'sort_order' => 'integer',
            'config' => 'array',
        ];
    }

    public function isPrepay(): bool
    {
        return $this->timing === self::TIMING_PREPAY;
    }

    public function isOnDelivery(): bool
    {
        return $this->timing === self::TIMING_ON_DELIVERY;
    }

    /**
     * @return HasMany<CheckoutSession, $this>
     */
    public function checkoutSessions(): HasMany
    {
        return $this->hasMany(CheckoutSession::class);
    }
}
