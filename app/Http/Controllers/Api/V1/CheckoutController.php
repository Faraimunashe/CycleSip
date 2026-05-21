<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Mobile\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {
    }

    public function preview(Request $request): JsonResponse
    {
        return $this->ok($this->checkoutService->preview($request->user()));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'delivery_instructions' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in(['cash', 'ecocash', 'innbucks', 'swipe'])],
            'store_scope' => ['required', Rule::in(['all', 'single'])],
            'selected_store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'delivering_for_someone' => ['nullable', 'boolean'],
            'recipient_name' => ['nullable', 'string', 'max:120', 'required_if:delivering_for_someone,1,true'],
            'recipient_phone' => ['nullable', 'string', 'max:32', 'required_if:delivering_for_someone,1,true'],
        ]);

        $orders = $this->checkoutService->process(
            user: $request->user(),
            validated: $validated,
            request: $request,
        );

        return $this->created([
            'order_ids' => $orders->pluck('id')->values(),
            'orders' => $orders->map(fn ($order): array => [
                'id' => $order->id,
                'store_id' => $order->store_id,
                'status' => $order->status,
                'total_amount' => (float) $order->total_amount,
            ])->values(),
        ], "Checkout completed. {$orders->count()} order(s) placed.");
    }
}
