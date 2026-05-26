<?php

namespace App\Services\Payments;

use App\Models\PaymentMethod;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PaymentMethodService
{
    /**
     * @return Collection<int, PaymentMethod>
     */
    public function enabled(): Collection
    {
        return PaymentMethod::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<string>
     */
    public function enabledCodes(): array
    {
        return $this->enabled()->pluck('code')->all();
    }

    public function findEnabledByCode(string $code): PaymentMethod
    {
        $method = PaymentMethod::query()
            ->where('code', $code)
            ->where('is_enabled', true)
            ->first();

        if (! $method) {
            throw ValidationException::withMessages([
                'payment_method' => 'The selected payment method is unavailable.',
            ]);
        }

        return $method;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toCheckoutOptions(): array
    {
        return $this->enabled()->map(fn (PaymentMethod $method): array => [
            'code' => $method->code,
            'name' => $method->name,
            'description' => $method->description,
            'timing' => $method->timing,
            'requires_phone' => $method->requires_phone,
            'gateway' => $method->gateway,
        ])->values()->all();
    }

    public function initialPaymentStatus(PaymentMethod $method): string
    {
        return $method->isPrepay()
            ? \App\Models\Order::PAYMENT_STATUS_AWAITING
            : \App\Models\Order::PAYMENT_STATUS_PENDING_COLLECTION;
    }
}
