<?php

namespace App\Services\Payments;

use App\Models\CheckoutSession;

interface PaymentGatewayInterface
{
    public function gatewayCode(): string;

    /**
     * @return array<string, mixed>
     */
    public function charge(CheckoutSession $session): array;
}
