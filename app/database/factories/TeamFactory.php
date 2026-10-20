<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
        ];
    }
}
