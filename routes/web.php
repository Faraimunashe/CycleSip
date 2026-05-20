<?php

use App\Http\Controllers\Admin\OrderManagementController;
use App\Http\Controllers\Admin\ProductManagementController;
use App\Http\Controllers\Admin\RiderManagementController;
use App\Http\Controllers\Admin\StoreManagementController;
use App\Http\Controllers\Admin\ZoneManagementController;
use App\Http\Controllers\Admin\CustomerManagementController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
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
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
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
