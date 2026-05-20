<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreProduct extends Model
{
    protected $fillable = [
        'store_id',
        'product_id',
        'price',
        'stock_quantity',
        'is_available',
        'promotion_price',
        'promotion_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'promotion_ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
