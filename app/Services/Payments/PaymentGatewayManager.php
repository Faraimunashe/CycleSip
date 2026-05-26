<?php

namespace App\Services\Payments;

use App\Models\PaymentMethod;

class PaymentGatewayManager
{
    public function __construct(
        private readonly EcocashPaymentService $ecocashPaymentService,
        private readonly InnbucksPaymentService $innbucksPaymentService,
    ) {
    }

    public function forPaymentMethod(PaymentMethod $paymentMethod): PaymentGatewayInterface
    {
        return match ($paymentMethod->gateway) {
            'ecocash' => $this->ecocashPaymentService,
            'innbucks' => $this->innbucksPaymentService,
            default => throw new PaymentGatewayException("No payment gateway configured for {$paymentMethod->code}."),
        };
    }
}
