<?php

namespace App\Providers;

use App\Events\OrderAvailableForRiders;
use App\Events\OrderStatusChanged;
use App\Listeners\SendOrderAvailablePushNotification;
use App\Listeners\SendOrderStatusPushNotification;
use App\Models\Order;
use App\Models\RiderProfile;
use App\Models\Store;
use App\Policies\OrderPolicy;
use App\Policies\RiderProfilePolicy;
use App\Policies\StorePolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(OrderStatusChanged::class, SendOrderStatusPushNotification::class);
        Event::listen(OrderAvailableForRiders::class, SendOrderAvailablePushNotification::class);

        Gate::before(function ($user, string $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }

            return null;
        });

        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Store::class, StorePolicy::class);
        Gate::policy(RiderProfile::class, RiderProfilePolicy::class);

        foreach ($this->permissionAbilities() as $ability) {
            Gate::define($ability, fn ($user): bool => $user->hasPermission($ability));
        }
    }

    /**
     * @return list<string>
     */
    private function permissionAbilities(): array
    {
        return [
            'manage-orders',
            'manage-riders',
            'manage-stores',
            'manage-users',
            'manage-settings',
            'view-analytics',
            'manage-payments',
            'manage-zones',
            'approve-riders',
            'approve-stores',
            'manage-products',
            'manage-customers',
        ];
    }
}
