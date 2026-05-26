<?php

namespace Database\Factories;

use App\Models\Host;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        $title = fake()->streetName().' '.fake()->buildingNumber();

        return [
            'host_id' => Host::factory(),
            'slug' => Str::slug($title).'-'.Str::random(6),
            'title' => $title,
            'description' => fake()->paragraph(),
            'property_type' => fake()->randomElement(['apartment', 'house', 'studio', 'villa']),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'county' => 'Bucuresti',
            'country' => 'RO',
            'postal_code' => fake()->postcode(),
            'base_price' => fake()->randomFloat(2, 100, 800),
            'currency' => 'RON',
            'cleaning_fee' => fake()->randomFloat(2, 30, 150),
            'max_guests' => fake()->numberBetween(2, 8),
            'bedrooms' => fake()->numberBetween(1, 4),
            'beds' => fake()->numberBetween(1, 5),
            'bathrooms' => fake()->numberBetween(1, 3),
            'check_in_time' => '15:00',
            'check_out_time' => '11:00',
            'min_nights' => 1,
            'max_nights' => 30,
            'house_rules' => null,
            'status' => 'published',
            'rating_avg' => 0,
            'reviews_count' => 0,
        ];
    }
}
