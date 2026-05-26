<?php

namespace Database\Factories;

use App\Models\Host;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Host>
 */
class HostFactory extends Factory
{
    protected $model = Host::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_name' => fake()->company(),
            'cui' => 'RO'.fake()->numerify('########'),
            'registration_number' => 'J'.fake()->numerify('##/####/####'),
            'billing_address' => fake()->streetAddress(),
            'billing_city' => fake()->city(),
            'billing_country' => 'RO',
            'vat_number' => null,
            'kyc_status' => 'approved',
            'kyc_documents' => [],
            'stripe_account_id' => null,
            'stripe_account_status' => 'pending',
            'onboarded_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['kyc_status' => 'pending', 'onboarded_at' => null]);
    }
}
