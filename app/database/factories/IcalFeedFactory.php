<?php

namespace Database\Factories;

use App\Models\IcalFeed;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IcalFeed>
 */
class IcalFeedFactory extends Factory
{
    protected $model = IcalFeed::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'direction' => 'in',
            'source_name' => fake()->randomElement(['airbnb', 'booking', 'vrbo']),
            'url' => 'https://example.com/ical/'.fake()->uuid().'.ics',
            'is_active' => true,
            'events_count' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function out(): static
    {
        return $this->state([
            'direction' => 'out',
            'url' => null,
            'export_token' => Str::random(64),
        ]);
    }
}
