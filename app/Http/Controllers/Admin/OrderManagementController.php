<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderManagementController extends Controller
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderWorkflowService $orderWorkflowService,
    ) {
    }

    public function index(Request $request): Response
    {
        $orders = $this->orderRepository->paginatedForOps(
            filters: $request->only(['search', 'status', 'payment_method']),
            perPage: 12
        );

        return Inertia::render('Admin/Orders/Index', [
            'orders' => OrderResource::collection($orders)->response()->getData(true),
            'statuses' => Order::ALLOWED_STATUSES,
            'filters' => $request->only(['search', 'status', 'payment_method']),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();

        $this->orderWorkflowService->transition(
            order: $order,
            toStatus: $validated['status'],
            userId: $request->user()->id,
            note: $validated['note'] ?? null,
        );

        return to_route('admin.orders.show', $order)->with('success', 'Order status updated.');
    }

    public function show(Order $order): Response
    {
        $order->load(['user', 'store', 'rider', 'zone', 'items.product', 'timeline.changedBy']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => OrderResource::make($order)->resolve(),
        ]);
    }

    public function edit(Order $order): Response
    {
        $order->load(['user', 'store', 'rider', 'zone', 'items.product', 'timeline.changedBy']);

        return Inertia::render('Admin/Orders/Edit', [
            'order' => OrderResource::make($order)->resolve(),
            'statuses' => Order::ALLOWED_STATUSES,
        ]);
    }
}
