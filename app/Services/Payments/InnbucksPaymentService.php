<?php

namespace App\Services\Payments;

use App\Models\CheckoutSession;

class InnbucksPaymentService implements PaymentGatewayInterface
{
    public function gatewayCode(): string
    {
        return 'innbucks';
    }

    /**
     * @return array<string, mixed>
     */
    public function charge(CheckoutSession $session): array
    {
        throw new PaymentGatewayException('InnBucks payments are not configured yet.');
    }
}
