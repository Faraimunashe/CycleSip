<?php

use App\Http\Controllers\Api\Operations\OrderFeedController;
use App\Http\Controllers\Api\Rider\RiderPresenceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/ops/orders', [OrderFeedController::class, 'index'])->middleware('can:manage-orders');
    Route::patch('/rider/presence', [RiderPresenceController::class, 'update']);
});
