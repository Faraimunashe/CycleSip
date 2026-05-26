<?php

use App\Http\Controllers\Api\Operations\OrderFeedController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\CustomerOrderController;
use App\Http\Controllers\Api\V1\IdentityController;
use App\Http\Controllers\Api\V1\PushTokenController;
use App\Http\Controllers\Api\V1\Rider\DashboardController as RiderDashboardController;
use App\Http\Controllers\Api\V1\Rider\LocationController as RiderLocationController;
use App\Http\Controllers\Api\V1\Rider\OrderController as RiderOrderController;
use App\Http\Controllers\Api\V1\Rider\PresenceController as RiderPresenceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('auth:sanctum')->post('broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    });

    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('password/forgot', [AuthController::class, 'forgotPassword']);
        Route::post('password/reset', [AuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::post('email/verify', [AuthController::class, 'verifyEmail']);
            Route::post('email/resend', [AuthController::class, 'resendEmailCode']);
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('push-tokens', [PushTokenController::class, 'store']);
        Route::delete('push-tokens', [PushTokenController::class, 'destroy']);
    });

    Route::middleware(['auth:sanctum', 'verified.api.email'])->group(function (): void {
        Route::get('addresses', [AddressController::class, 'index']);
        Route::get('addresses/coverage', [AddressController::class, 'coverage']);
        Route::post('addresses', [AddressController::class, 'store']);
        Route::post('addresses/{address}/select', [AddressController::class, 'select']);

        Route::get('compliance/identity', [IdentityController::class, 'show']);
        Route::post('compliance/identity', [IdentityController::class, 'store']);

        Route::middleware('delivery.address.api')->group(function (): void {
            Route::get('catalog', [CatalogController::class, 'index']);

            Route::get('cart', [CartController::class, 'show']);
            Route::post('cart/items', [CartController::class, 'addItem']);
            Route::patch('cart/items/{storeProduct}', [CartController::class, 'updateItem']);
            Route::delete('cart/items/{storeProduct}', [CartController::class, 'removeItem']);
            Route::delete('cart', [CartController::class, 'clear']);

            Route::get('checkout/preview', [CheckoutController::class, 'preview']);
            Route::post('checkout', [CheckoutController::class, 'store']);
            Route::post('checkout/pay', [CheckoutController::class, 'pay']);
            Route::get('checkout/sessions/{uuid}', [CheckoutController::class, 'showSession']);
        });

        Route::get('orders', [CustomerOrderController::class, 'index']);
        Route::get('orders/{order}', [CustomerOrderController::class, 'show']);
        Route::post('orders/{order}/ratings/order', [CustomerOrderController::class, 'rateOrder']);
        Route::post('orders/{order}/ratings/rider', [CustomerOrderController::class, 'rateRider']);
    });

    Route::middleware(['auth:sanctum', 'rider.api'])->prefix('rider')->group(function (): void {
        Route::get('dashboard', [RiderDashboardController::class, 'index']);
        Route::get('orders/available', [RiderOrderController::class, 'available']);
        Route::get('orders/active', [RiderOrderController::class, 'active']);
        Route::get('orders/history', [RiderOrderController::class, 'history']);
        Route::get('orders/{order}', [RiderOrderController::class, 'show']);
        Route::post('orders/{order}/accept', [RiderOrderController::class, 'accept']);
        Route::patch('orders/{order}/status', [RiderOrderController::class, 'updateStatus']);
        Route::patch('presence', [RiderPresenceController::class, 'update']);
        Route::patch('location', [RiderLocationController::class, 'update']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/ops/orders', [OrderFeedController::class, 'index'])->middleware('can:manage-orders');
});
