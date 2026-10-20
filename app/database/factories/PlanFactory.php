<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'slug' => Str::slug(fake()->unique()->words(2, true)),
            'name_ro' => ucfirst(fake()->unique()->words(2, true)),
            'name_en' => ucfirst(fake()->unique()->words(2, true)),
            'description_ro' => fake()->sentence(),
            'description_en' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 49, 499),
            'currency' => 'RON',
            'interval' => 'month',
            'features' => [],
            'max_properties' => null,
            'platform_fee_pct' => fake()->randomElement([5, 8, 10, 12]),
            'stripe_price_id' => 'price_'.fake()->regexify('[A-Za-z0-9]{14}'),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function yearly(): static
    {
        return $this->state(['interval' => 'year']);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
