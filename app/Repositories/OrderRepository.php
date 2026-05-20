<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OrderRepository
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginatedForOps(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Order::query()
            ->with(['user', 'store', 'rider', 'zone', 'items.product', 'timeline.changedBy'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['payment_method'] ?? null, fn (Builder $query, string $paymentMethod) => $query->where('payment_method', $paymentMethod))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('id', $search)
                        ->orWhere('delivery_address', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
