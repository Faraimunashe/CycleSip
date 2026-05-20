<?php

namespace App\Services;

use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\RiderProfile;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $today = Carbon::today();
        $driver = DB::connection()->getDriverName();
        $dateExpression = $this->dateBucketExpression($driver);
        $weekExpression = $this->weekBucketExpression($driver);
        $hourExpression = $this->hourBucketExpression($driver);

        $dailyOrders = Order::query()
            ->selectRaw("{$dateExpression} as date, COUNT(*) as total, SUM(total_amount) as revenue")
            ->where('created_at', '>=', now()->subDays(13))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $weeklyTrends = Order::query()
            ->selectRaw("{$weekExpression} as week, COUNT(*) as total")
            ->where('created_at', '>=', now()->subWeeks(8))
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        $riderPerformance = RiderProfile::query()
            ->with('user')
            ->orderByDesc('completed_deliveries')
            ->limit(6)
            ->get()
            ->map(fn (RiderProfile $rider): array => [
                'name' => $rider->user->name,
                'completed_deliveries' => $rider->completed_deliveries,
                'acceptance_rate' => (float) $rider->acceptance_rate,
                'cancellation_rate' => (float) $rider->cancellation_rate,
            ]);

        $zones = DeliveryZone::query()
            ->withCount('orders')
            ->orderByDesc('orders_count')
            ->limit(5)
            ->get()
            ->map(fn (DeliveryZone $zone): array => [
                'name' => $zone->name,
                'orders' => $zone->orders_count,
            ]);

        $topProducts = Product::query()
            ->join('order_items', 'order_items.product_id', '=', 'products.id')
            ->selectRaw('products.name, SUM(order_items.quantity) as total_qty')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get()
            ->map(fn ($item): array => [
                'name' => $item->name,
                'total_qty' => (int) $item->total_qty,
            ]);

        $peakHours = Order::query()
            ->selectRaw("{$hourExpression} as hour, COUNT(*) as total")
            ->groupBy('hour')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($item): array => [
                'hour' => str_pad((string) $item->hour, 2, '0', STR_PAD_LEFT).':00',
                'orders' => (int) $item->total,
            ]);

        $revenueBreakdown = Order::query()
            ->selectRaw('payment_method, SUM(total_amount) as total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($item): array => [
                'label' => strtoupper($item->payment_method),
                'value' => (float) $item->total,
            ]);

        return [
            'summary' => [
                'total_orders_today' => Order::query()->whereDate('created_at', $today)->count(),
                'total_revenue' => (float) Order::query()->sum('total_amount'),
                'active_riders' => RiderProfile::query()->where('approval_status', 'approved')->count(),
                'online_riders' => RiderProfile::query()->where('approval_status', 'approved')->where('is_online', true)->count(),
                'pending_orders' => Order::query()->where('status', Order::STATUS_PENDING)->count(),
                'completed_orders' => Order::query()->where('status', Order::STATUS_COMPLETED)->count(),
                'cancelled_orders' => Order::query()->where('status', Order::STATUS_CANCELLED)->count(),
                'stores_count' => Store::query()->count(),
                'customers_count' => User::query()->whereHas('roles', fn ($query) => $query->where('name', 'customer'))->count(),
            ],
            'charts' => [
                'daily_sales' => $this->formatDaily($dailyOrders),
                'weekly_orders' => $weeklyTrends,
                'rider_performance' => $riderPerformance,
                'top_zones' => $zones,
                'top_products' => $topProducts,
                'peak_hours' => $peakHours,
                'revenue_breakdown' => $revenueBreakdown,
            ],
            'activity_feed' => $this->latestActivity(),
        ];
    }

    /**
     * @param  Collection<int, object>  $dailyOrders
     * @return list<array{date: string, total: int, revenue: float}>
     */
    private function formatDaily(Collection $dailyOrders): array
    {
        return $dailyOrders->map(fn ($row): array => [
            'date' => (string) $row->date,
            'total' => (int) $row->total,
            'revenue' => (float) $row->revenue,
        ])->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function latestActivity(): array
    {
        return DB::table('activity_logs')
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn ($row): array => [
                'id' => $row->id,
                'event' => $row->event,
                'created_at' => $row->created_at,
                'metadata' => $row->metadata ? json_decode($row->metadata, true) : null,
            ])
            ->values()
            ->all();
    }

    private function dateBucketExpression(string $driver): string
    {
        return match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
            default => 'DATE(created_at)',
        };
    }

    private function weekBucketExpression(string $driver): string
    {
        return match ($driver) {
            'sqlite' => "strftime('%Y-W%W', created_at)",
            'pgsql' => "to_char(created_at, 'IYYY-\"W\"IW')",
            default => "DATE_FORMAT(created_at, '%x-W%v')",
        };
    }

    private function hourBucketExpression(string $driver): string
    {
        return match ($driver) {
            'sqlite' => "strftime('%H', created_at)",
            'pgsql' => "to_char(created_at, 'HH24')",
            default => 'HOUR(created_at)',
        };
    }
}
