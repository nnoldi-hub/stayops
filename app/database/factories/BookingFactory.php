<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('+1 days', '+30 days');
        $nights = fake()->numberBetween(1, 7);
        $checkOut = (clone $checkIn)->modify("+{$nights} days");
        $rate = fake()->randomFloat(2, 150, 600);
        $subtotal = round($rate * $nights, 2);
        $cleaning = 80;
        $platform = round($subtotal * 0.1, 2);
        $total = round($subtotal + $cleaning + $platform, 2);

        return [
            'reference' => 'BK-'.strtoupper(Str::random(8)),
            'property_id' => Property::factory(),
            'guest_id' => null,
            'guest_name' => fake()->name(),
            'guest_email' => fake()->unique()->safeEmail(),
            'guest_phone' => fake()->phoneNumber(),
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'nights' => $nights,
            'guests_count' => fake()->numberBetween(1, 4),
            'adults' => fake()->numberBetween(1, 3),
            'children' => 0,
            'nightly_rate' => $rate,
            'subtotal' => $subtotal,
            'cleaning_fee' => $cleaning,
            'platform_fee' => $platform,
            'host_payout' => $subtotal - $platform,
            'total_price' => $total,
            'currency' => 'RON',
            'status' => 'pending',
            'source' => 'direct',
            'payment_status' => 'pending',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => 'confirmed', 'payment_status' => 'paid']);
    }
}
