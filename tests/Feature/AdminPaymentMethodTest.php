<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\User;
use Database\Seeders\LaratrustSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LaratrustSeeder::class);
        $this->seed(PaymentMethodSeeder::class);
    }

    public function test_admin_can_view_and_update_payment_methods(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $admin->addRole('admin');

        $method = PaymentMethod::query()->where('code', 'innbucks')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/payment-methods')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/PaymentMethods/Index', false)
                ->has('paymentMethods', 4));

        $this->actingAs($admin)
            ->patch("/admin/payment-methods/{$method->id}", [
                'name' => 'InnBucks Wallet',
                'description' => 'Pay with InnBucks before delivery.',
                'timing' => PaymentMethod::TIMING_PREPAY,
                'is_enabled' => true,
                'requires_phone' => true,
                'sort_order' => 5,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $method->refresh();
        $this->assertTrue($method->is_enabled);
        $this->assertSame('InnBucks Wallet', $method->name);
    }

    public function test_customer_cannot_manage_payment_methods(): void
    {
        $customer = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $customer->addRole('customer');

        $this->actingAs($customer)
            ->get('/admin/payment-methods')
            ->assertForbidden();
    }
}
