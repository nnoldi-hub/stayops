<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Provider>
 */
class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_name' => fake()->company(),
            'cui' => 'RO'.fake()->numerify('########'),
            'service_types' => ['cleaning', 'repair'],
            'coverage_cities' => [fake()->city()],
            'kyc_status' => 'approved',
            'kyc_documents' => [],
            'rating_avg' => 0,
            'jobs_completed' => 0,
            'is_active' => true,
        ];
    }

    public function pending(): static
    {
        return $this->state(['kyc_status' => 'pending']);
    }
}
