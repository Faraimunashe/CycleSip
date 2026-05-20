<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_BROADCAST_TO_RIDERS = 'broadcast_to_riders';
    public const STATUS_ACCEPTED_BY_RIDER = 'accepted_by_rider';
    public const STATUS_EN_ROUTE_TO_STORE = 'en_route_to_store';
    public const STATUS_VERIFYING_STOCK = 'verifying_stock';
    public const STATUS_COLLECTING_ITEMS = 'collecting_items';
    public const STATUS_ADJUSTED = 'adjusted';
    public const STATUS_EN_ROUTE_TO_CUSTOMER = 'en_route_to_customer';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const ALLOWED_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_BROADCAST_TO_RIDERS,
        self::STATUS_ACCEPTED_BY_RIDER,
        self::STATUS_EN_ROUTE_TO_STORE,
        self::STATUS_VERIFYING_STOCK,
        self::STATUS_COLLECTING_ITEMS,
        self::STATUS_ADJUSTED,
        self::STATUS_EN_ROUTE_TO_CUSTOMER,
        self::STATUS_DELIVERED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'user_id',
        'rider_id',
        'store_id',
        'delivery_zone_id',
        'delivery_address_id',
        'status',
        'payment_method',
        'subtotal_amount',
        'delivery_fee',
        'platform_commission',
        'total_amount',
        'payment_status',
        'delivery_address',
        'customer_phone',
        'recipient_name',
        'recipient_phone',
        'notes',
        'delivery_instructions',
        'placed_at',
        'accepted_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'placed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    /**
     * @return BelongsTo<DeliveryZone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    /**
     * @return BelongsTo<UserAddress, $this>
     */
    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'delivery_address_id');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderStatusTimeline, $this>
     */
    public function timeline(): HasMany
    {
        return $this->hasMany(OrderStatusTimeline::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasOne<OrderRating, $this>
     */
    public function orderRating(): HasOne
    {
        return $this->hasOne(OrderRating::class);
    }

    /**
     * @return HasOne<RiderRating, $this>
     */
    public function riderRating(): HasOne
    {
        return $this->hasOne(RiderRating::class);
    }
}
