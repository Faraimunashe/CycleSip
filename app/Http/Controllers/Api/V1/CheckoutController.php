<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use App\Rules\ZimbabwePhone;
use App\Services\Mobile\CheckoutService;
use App\Services\Payments\PaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly PaymentMethodService $paymentMethodService,
    ) {
    }

    public function preview(Request $request): JsonResponse
    {
        return $this->ok($this->checkoutService->preview($request->user()));
    }

    public function store(Request $request): JsonResponse
    {
        $paymentMethodCode = $request->input('payment_method');

        if (is_string($paymentMethodCode) && $paymentMethodCode !== '') {
            $method = $this->paymentMethodService->findEnabledByCode($paymentMethodCode);

            if ($method->isPrepay()) {
                return $this->pay($request);
            }
        }

        $validated = $this->validateCheckout($request, prepayAllowed: false);

        $orders = $this->checkoutService->processOnDelivery(
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
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'total_amount' => (float) $order->total_amount,
            ])->values(),
        ], "Checkout completed. {$orders->count()} order(s) placed.");
    }

    public function pay(Request $request): JsonResponse
    {
        $validated = $this->validateCheckout($request, prepayAllowed: true);

        $result = $this->checkoutService->processPrepaid(
            user: $request->user(),
            validated: $validated,
            request: $request,
        );

        /** @var CheckoutSession $session */
        $session = $result['checkout_session'];
        $orders = $result['orders'];

        return $this->created([
            'checkout_session_id' => $session->uuid,
            'payment_status' => $session->status,
            'order_ids' => $orders->pluck('id')->values(),
            'orders' => $orders->map(fn ($order): array => [
                'id' => $order->id,
                'store_id' => $order->store_id,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'total_amount' => (float) $order->total_amount,
            ])->values(),
        ], 'Payment confirmed. Your order has been placed.');
    }

    public function showSession(Request $request, string $uuid): JsonResponse
    {
        $session = $this->checkoutService->sessionForUser($request->user(), $uuid);

        return $this->ok([
            'checkout_session_id' => $session->uuid,
            'status' => $session->status,
            'payment_method' => $session->payment_method_code,
            'amount' => (float) $session->amount,
            'currency' => $session->currency,
            'paid_at' => optional($session->paid_at)?->toIso8601String(),
            'order_ids' => $session->orders->pluck('id')->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCheckout(Request $request, bool $prepayAllowed): array
    {
        $enabledCodes = $this->paymentMethodService->enabledCodes();
        $paymentMethod = $request->input('payment_method');
        $methodModel = $paymentMethod
            ? $this->paymentMethodService->findEnabledByCode((string) $paymentMethod)
            : null;

        if ($methodModel && ! $prepayAllowed && $methodModel->isPrepay()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Use the prepaid checkout endpoint for this payment method.',
            ]);
        }

        if ($methodModel && $prepayAllowed && $methodModel->isOnDelivery()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Use the standard checkout endpoint for this payment method.',
            ]);
        }

        return $request->validate([
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'customer_msisdn' => [
                Rule::requiredIf(fn (): bool => (bool) ($methodModel?->requires_phone)),
                'nullable',
                'string',
                'max:20',
                new ZimbabwePhone(),
            ],
            'delivery_instructions' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'string', Rule::in($enabledCodes)],
            'store_scope' => ['required', Rule::in(['all', 'single'])],
            'selected_store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'delivering_for_someone' => ['nullable', 'boolean'],
            'recipient_name' => ['nullable', 'string', 'max:120', 'required_if:delivering_for_someone,1,true'],
            'recipient_phone' => ['nullable', 'string', 'max:32', 'required_if:delivering_for_someone,1,true'],
        ]);
    }
}
