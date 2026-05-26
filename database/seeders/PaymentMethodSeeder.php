<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'code' => 'cash',
                'name' => 'Cash',
                'description' => 'Pay the rider in cash when your order is delivered.',
                'timing' => PaymentMethod::TIMING_ON_DELIVERY,
                'gateway' => null,
                'is_enabled' => true,
                'requires_phone' => false,
                'sort_order' => 1,
            ],
            [
                'code' => 'swipe',
                'name' => 'Card Swipe',
                'description' => 'Pay by card swipe when your order is delivered.',
                'timing' => PaymentMethod::TIMING_ON_DELIVERY,
                'gateway' => null,
                'is_enabled' => true,
                'requires_phone' => false,
                'sort_order' => 2,
            ],
            [
                'code' => 'ecocash',
                'name' => 'EcoCash',
                'description' => 'Pay instantly with EcoCash before your order is sent to riders.',
                'timing' => PaymentMethod::TIMING_PREPAY,
                'gateway' => 'ecocash',
                'is_enabled' => true,
                'requires_phone' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'innbucks',
                'name' => 'InnBucks',
                'description' => 'Pay with InnBucks before your order is sent to riders.',
                'timing' => PaymentMethod::TIMING_PREPAY,
                'gateway' => 'innbucks',
                'is_enabled' => false,
                'requires_phone' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $method['code']],
                $method,
            );
        }
    }
}
