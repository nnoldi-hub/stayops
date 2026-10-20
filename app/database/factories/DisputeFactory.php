<?php

namespace Database\Factories;

use App\Models\Dispute;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dispute>
 */
class DisputeFactory extends Factory
{
    protected $model = Dispute::class;

    public function definition(): array
    {
        return [
            'reference' => 'DSP-'.strtoupper(fake()->unique()->bothify('######')),
            'booking_id' => null,
            'task_id' => null,
            'opener_id' => User::factory(),
            'category' => fake()->randomElement(['payment', 'damage', 'service_quality', 'cancellation', 'other']),
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => 'open',
            'assigned_admin_id' => null,
            'resolution' => null,
            'refund_amount' => null,
            'resolved_at' => null,
        ];
    }

    public function investigating(): static
    {
        return $this->state(fn (): array => ['status' => 'investigating']);
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => 'resolved',
            'resolution' => fake()->paragraph(),
            'resolved_at' => now(),
        ]);
    }
}
