<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIdentityDocument extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TYPE_NATIONAL_ID = 'national_id';
    public const TYPE_DRIVERS_LICENSE = 'drivers_license';
    public const TYPE_PASSPORT = 'passport';

    public const DOCUMENT_TYPES = [
        self::TYPE_NATIONAL_ID,
        self::TYPE_DRIVERS_LICENSE,
        self::TYPE_PASSPORT,
    ];

    protected $fillable = [
        'user_id',
        'document_type',
        'file_url',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
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
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
