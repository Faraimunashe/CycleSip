<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Mobile\RiderOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private readonly RiderOrderService $riderOrderService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $rider = $request->user();

        $assignedOrders = Order::query()
            ->where('rider_id', $rider->id)
            ->with(['store', 'user', 'orderRating.user', 'riderRating.user'])
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn (Order $order): array => $this->riderOrderService->toListArray($order))
            ->values();

        return $this->ok([
            'metrics' => [
                'available_orders' => Order::query()
                    ->whereNull('rider_id')
                    ->where('status', Order::STATUS_BROADCAST_TO_RIDERS)
                    ->count(),
                'active_deliveries' => $assignedOrders->whereIn('status', [
                    Order::STATUS_ACCEPTED_BY_RIDER,
                    Order::STATUS_EN_ROUTE_TO_STORE,
                    Order::STATUS_VERIFYING_STOCK,
                    Order::STATUS_COLLECTING_ITEMS,
                    Order::STATUS_EN_ROUTE_TO_CUSTOMER,
                ])->count(),
                'completed_today' => Order::query()
                    ->where('rider_id', $rider->id)
                    ->whereDate('completed_at', today())
                    ->count(),
                'worked_orders' => Order::query()
                    ->where('rider_id', $rider->id)
                    ->count(),
            ],
            'assigned_orders' => $assignedOrders,
        ]);
    }
}
