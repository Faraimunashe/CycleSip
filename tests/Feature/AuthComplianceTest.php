<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EmailVerificationService;
use Database\Seeders\LaratrustSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_adult_date_of_birth(): void
    {
        $this->seed(LaratrustSeeder::class);

        $response = $this->post('/register', [
            'name' => 'Underage User',
            'email' => 'underage@example.com',
            'phone' => '+263771234567',
            'date_of_birth' => now()->subYears(17)->format('Y-m-d'),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('date_of_birth');
        $this->assertGuest();
    }

    public function test_registration_redirects_to_email_verification(): void
    {
        $this->seed(LaratrustSeeder::class);

        $response = $this->post('/register', [
            'name' => 'New Customer',
            'email' => 'newcustomer@example.com',
            'phone' => '+263771234567',
            'date_of_birth' => now()->subYears(25)->format('Y-m-d'),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/email/verify');
        $this->assertAuthenticated();

        $user = User::where('email', 'newcustomer@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('customer'));
        $this->assertNotNull($user->age_verified_at);
        $this->assertNull($user->email_verified_at);
    }

    public function test_unverified_customer_cannot_access_products(): void
    {
        $this->seed(LaratrustSeeder::class);

        $customer = User::factory()->unverified()->create([
            'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
            'age_verified_at' => now(),
        ]);
        $customer->addRole('customer');

        $response = $this->actingAs($customer)->get('/products');

        $response->assertRedirect('/email/verify');
    }

    public function test_customer_can_verify_email_with_otp(): void
    {
        $this->seed(LaratrustSeeder::class);

        $customer = User::factory()->unverified()->create([
            'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
            'age_verified_at' => now(),
        ]);
        $customer->addRole('customer');

        $plainCode = app(EmailVerificationService::class)->issueCode($customer);

        $response = $this->actingAs($customer)->post('/email/verify', [
            'code' => $plainCode,
        ]);

        $response->assertRedirect('/addresses/select');
        $this->assertNotNull($customer->fresh()->email_verified_at);
    }

    public function test_registration_requires_valid_zimbabwe_phone_with_country_code(): void
    {
        $this->seed(LaratrustSeeder::class);

        $response = $this->post('/register', [
            'name' => 'Invalid Phone User',
            'email' => 'invalidphone@example.com',
            'phone' => '0771234567',
            'date_of_birth' => now()->subYears(25)->format('Y-m-d'),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_forgot_password_page_is_accessible(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertOk();
    }
}
