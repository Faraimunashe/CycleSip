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

        $activeOrders = Order::query()
            ->where('rider_id', $rider->id)
            ->whereIn('status', RiderOrderService::activeStatuses())
            ->with(['store', 'user', 'orderRating.user', 'riderRating.user'])
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Order $order): array => $this->riderOrderService->toListArray($order))
            ->values();

        $activeCount = Order::query()
            ->where('rider_id', $rider->id)
            ->whereIn('status', RiderOrderService::activeStatuses())
            ->count();

        return $this->ok([
            'metrics' => [
                'available_orders' => Order::query()
                    ->whereNull('rider_id')
                    ->where('status', Order::STATUS_BROADCAST_TO_RIDERS)
                    ->count(),
                'active_deliveries' => $activeCount,
                'completed_today' => Order::query()
                    ->where('rider_id', $rider->id)
                    ->whereDate('completed_at', today())
                    ->count(),
                'worked_orders' => Order::query()
                    ->where('rider_id', $rider->id)
                    ->count(),
            ],
            'active_orders' => $activeOrders,
            'active_orders_total' => $activeCount,
        ]);
    }
}
