<?php

use App\Http\Controllers\Admin\OrderManagementController;
use App\Http\Controllers\Admin\ProductManagementController;
use App\Http\Controllers\Admin\RiderManagementController;
use App\Http\Controllers\Admin\StoreManagementController;
use App\Http\Controllers\Admin\ZoneManagementController;
use App\Http\Controllers\Admin\CustomerManagementController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\AddressSelectionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RiderDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('products.index');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/rider/dashboard', [RiderDashboardController::class, 'index'])->name('rider.dashboard');
    Route::get('/rider/orders/available', [RiderDashboardController::class, 'available'])->name('rider.orders.available');
    Route::get('/rider/orders/history', [RiderDashboardController::class, 'history'])->name('rider.orders.history');
    Route::get('/rider/orders/{order}', [RiderDashboardController::class, 'show'])->name('rider.orders.show');
    Route::patch('/rider/orders/{order}/accept', [RiderDashboardController::class, 'accept'])->name('rider.orders.accept');
    Route::patch('/rider/orders/{order}/status', [RiderDashboardController::class, 'updateStatus'])->name('rider.orders.status.update');

    Route::get('/addresses/select', [AddressSelectionController::class, 'select'])->name('addresses.select');
    Route::post('/addresses', [AddressSelectionController::class, 'store'])->name('addresses.store');
    Route::post('/addresses/{address}/use', [AddressSelectionController::class, 'use'])->name('addresses.use');

    Route::middleware('delivery.address.selected')->group(function (): void {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/cart/items', [OrderController::class, 'addToCart'])->name('cart.items.store');
        Route::patch('/cart/items/{storeProduct}', [OrderController::class, 'updateCartItem'])->name('cart.items.update');
        Route::delete('/cart/items/{storeProduct}', [OrderController::class, 'removeCartItem'])->name('cart.items.destroy');
        Route::delete('/cart', [OrderController::class, 'clearCart'])->name('cart.clear');
        Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout.index');
        Route::post('/checkout', [OrderController::class, 'processCheckout'])->name('checkout.store');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/ratings/order', [OrderController::class, 'rateOrder'])->name('orders.ratings.order.store');
        Route::post('/orders/{order}/ratings/rider', [OrderController::class, 'rateRider'])->name('orders.ratings.rider.store');
    });

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('can:view-analytics')
        ->name('dashboard');

    Route::get('/orders', [OrderManagementController::class, 'index'])
        ->middleware('can:manage-orders')
        ->name('orders.index');
    Route::get('/orders/{order}', [OrderManagementController::class, 'show'])
        ->middleware('can:manage-orders')
        ->name('orders.show');
    Route::get('/orders/{order}/edit', [OrderManagementController::class, 'edit'])
        ->middleware('can:manage-orders')
        ->name('orders.edit');
    Route::patch('/orders/{order}/items', [OrderManagementController::class, 'adjustItems'])
        ->middleware('can:manage-orders')
        ->name('orders.items.adjust');
    Route::patch('/orders/{order}/status', [OrderManagementController::class, 'updateStatus'])
        ->middleware('can:manage-orders')
        ->name('orders.status.update');

    Route::get('/riders', [RiderManagementController::class, 'index'])
        ->middleware('can:manage-riders')
        ->name('riders.index');
    Route::get('/riders/{rider}', [RiderManagementController::class, 'show'])
        ->middleware('can:manage-riders')
        ->name('riders.show');
    Route::get('/riders/{rider}/edit', [RiderManagementController::class, 'edit'])
        ->middleware('can:approve-riders')
        ->name('riders.edit');
    Route::patch('/riders/{rider}/approval', [RiderManagementController::class, 'approve'])
        ->middleware('can:approve-riders')
        ->name('riders.approval.update');

    Route::get('/stores', [StoreManagementController::class, 'index'])
        ->middleware('can:manage-stores')
        ->name('stores.index');
    Route::get('/stores/create', [StoreManagementController::class, 'create'])
        ->middleware('can:manage-stores')
        ->name('stores.create');
    Route::post('/stores', [StoreManagementController::class, 'store'])
        ->middleware('can:manage-stores')
        ->name('stores.store');
    Route::get('/stores/{store}', [StoreManagementController::class, 'show'])
        ->middleware('can:manage-stores')
        ->name('stores.show');
    Route::get('/stores/{store}/edit', [StoreManagementController::class, 'edit'])
        ->middleware('can:manage-stores')
        ->name('stores.edit');
    Route::put('/stores/{store}', [StoreManagementController::class, 'update'])
        ->middleware('can:manage-stores')
        ->name('stores.update');

    Route::get('/products', [ProductManagementController::class, 'index'])
        ->middleware('can:manage-products')
        ->name('products.index');
    Route::get('/products/create', [ProductManagementController::class, 'create'])
        ->middleware('can:manage-products')
        ->name('products.create');
    Route::post('/products', [ProductManagementController::class, 'store'])
        ->middleware('can:manage-products')
        ->name('products.store');
    Route::get('/products/{product}', [ProductManagementController::class, 'show'])
        ->middleware('can:manage-products')
        ->name('products.show');
    Route::get('/products/{product}/edit', [ProductManagementController::class, 'edit'])
        ->middleware('can:manage-products')
        ->name('products.edit');
    Route::put('/products/{product}', [ProductManagementController::class, 'update'])
        ->middleware('can:manage-products')
        ->name('products.update');

    Route::get('/zones', [ZoneManagementController::class, 'index'])
        ->middleware('can:manage-zones')
        ->name('zones.index');
    Route::get('/zones/create', [ZoneManagementController::class, 'create'])
        ->middleware('can:manage-zones')
        ->name('zones.create');
    Route::post('/zones', [ZoneManagementController::class, 'store'])
        ->middleware('can:manage-zones')
        ->name('zones.store');
    Route::get('/zones/{zone}', [ZoneManagementController::class, 'show'])
        ->middleware('can:manage-zones')
        ->name('zones.show');
    Route::get('/zones/{zone}/edit', [ZoneManagementController::class, 'edit'])
        ->middleware('can:manage-zones')
        ->name('zones.edit');
    Route::put('/zones/{zone}', [ZoneManagementController::class, 'update'])
        ->middleware('can:manage-zones')
        ->name('zones.update');

    Route::get('/customers', [CustomerManagementController::class, 'index'])
        ->middleware('can:manage-customers')
        ->name('customers.index');

    Route::get('/finance', [FinanceController::class, 'index'])
        ->middleware('can:manage-payments')
        ->name('finance.index');
});

Route::get('/welcome', function () {
    return Inertia::render('WelcomePage');
})->name('welcome');
