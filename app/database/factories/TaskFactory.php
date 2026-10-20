<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'booking_id' => null,
            'type' => fake()->randomElement(['cleaning', 'repair', 'inspection', 'laundry', 'restock', 'other']),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'provider_id' => null,
            'assigned_to_user_id' => null,
            'status' => 'open',
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'scheduled_at' => now()->addDays(fake()->numberBetween(1, 7)),
            'started_at' => null,
            'completed_at' => null,
            'verified_at' => null,
            'estimated_cost' => fake()->randomFloat(2, 50, 500),
            'actual_cost' => null,
            'currency' => 'RON',
            'notes' => null,
            'checklist' => null,
        ];
    }
}
