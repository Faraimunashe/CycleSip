<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;

#[Fillable([
    'name',
    'email',
    'phone',
    'date_of_birth',
    'age_verified_at',
    'status',
    'last_seen_at',
    'selected_delivery_address_id',
    'password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements LaratrustUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    use HasRolesAndPermissions;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'age_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<UserAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class)->orderByDesc('is_default')->latest();
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function assignedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'rider_id');
    }

    /**
     * @return HasMany<RiderProfile, $this>
     */
    public function approvedRiders(): HasMany
    {
        return $this->hasMany(RiderProfile::class, 'approved_by');
    }

    /**
     * @return HasMany<Payout, $this>
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<SupportTicket, $this>
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'customer_id');
    }

    /**
     * @return HasMany<SupportTicket, $this>
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }

    /**
     * @return HasMany<OrderRating, $this>
     */
    public function orderRatings(): HasMany
    {
        return $this->hasMany(OrderRating::class);
    }

    /**
     * @return HasMany<RiderRating, $this>
     */
    public function riderRatingsGiven(): HasMany
    {
        return $this->hasMany(RiderRating::class);
    }

    /**
     * @return HasMany<RiderRating, $this>
     */
    public function riderRatingsReceived(): HasMany
    {
        return $this->hasMany(RiderRating::class, 'rider_id');
    }

    /**
     * @return HasMany<UserIdentityDocument, $this>
     */
    public function identityDocuments(): HasMany
    {
        return $this->hasMany(UserIdentityDocument::class);
    }

    /**
     * @return BelongsTo<UserAddress, $this>
     */
    public function selectedDeliveryAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'selected_delivery_address_id');
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * @return HasOne<RiderProfile, $this>
     */
    public function riderProfile(): HasOne
    {
        return $this->hasOne(RiderProfile::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function isAgeVerified(): bool
    {
        return $this->age_verified_at !== null;
    }
}
