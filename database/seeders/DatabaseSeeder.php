<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(LaratrustSeeder::class);
        $this->call(PaymentMethodSeeder::class);

        $roleUsers = [
            'super-admin' => ['Super Admin', 'superadmin@cyclesip.app'],
            'admin' => ['Admin User', 'admin@cyclesip.app'],
            'operations-manager' => ['Operations Manager', 'ops@cyclesip.app'],
            'support-staff' => ['Support Staff', 'support@cyclesip.app'],
            'finance-officer' => ['Finance Officer', 'finance@cyclesip.app'],
            'rider' => ['Rider User', 'rider@cyclesip.app'],
            'customer' => ['Customer User', 'customer@cyclesip.app'],
        ];

        foreach ($roleUsers as $role => [$name, $email]) {
            $user = User::factory()->create([
                'name' => $name,
                'email' => $email,
                'phone' => '+263771'.random_int(100000, 999999),
                'date_of_birth' => now()->subYears(22)->toDateString(),
                'age_verified_at' => now(),
                'status' => 'active',
                'password' => 'password',
            ]);
            $user->addRole($role);
        }

        User::factory()->count(10)->create()->each(function (User $user): void {
            $user->addRole('customer');
            $user->update([
                'date_of_birth' => now()->subYears(random_int(18, 35))->toDateString(),
                'age_verified_at' => now(),
                'status' => 'active',
            ]);
        });

        User::factory()->count(3)->create()->each(function (User $user): void {
            $user->addRole('rider');
            $user->update([
                'date_of_birth' => now()->subYears(random_int(20, 33))->toDateString(),
                'age_verified_at' => now(),
                'status' => 'active',
            ]);
        });

        $this->call(CatalogSeeder::class);
        $this->call(OperationalDataSeeder::class);
    }
}
