<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Host;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BookingTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeBooking(array $overrides = []): Booking
    {
        $user = User::factory()->create();
        $host = Host::create([
            'user_id' => $user->id,
            'company_name' => 'Test SRL',
            'kyc_status' => 'approved',
        ]);
        $property = Property::create(array_merge([
            'host_id' => $host->id,
            'slug' => 'test-property-'.uniqid(),
            'title' => 'Test',
            'description' => 'desc',
            'property_type' => 'apartment',
            'status' => 'published',
            'address' => 'Str. Test 1',
            'city' => 'Bucuresti',
            'country' => 'RO',
            'bedrooms' => 1,
            'beds' => 1,
            'bathrooms' => 1,
            'max_guests' => 2,
            'base_price' => 200,
            'cleaning_fee' => 50,
            'currency' => 'RON',
        ], $overrides['property'] ?? []));

        return Booking::create(array_merge([
            'reference' => 'BK-'.uniqid(),
            'property_id' => $property->id,
            'guest_name' => 'Ion Popescu',
            'guest_email' => 'ion@example.com',
            'check_in' => now()->addDays(7)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
            'nights' => 3,
            'guests_count' => 2,
            'adults' => 2,
            'nightly_rate' => 200,
            'subtotal' => 600,
            'cleaning_fee' => 50,
            'platform_fee' => 65,
            'host_payout' => 585,
            'total_price' => 650,
            'currency' => 'RON',
            'status' => 'pending',
            'source' => 'direct',
            'payment_status' => 'pending',
        ], $overrides['booking'] ?? []));
    }

    public function test_valid_transition_is_logged(): void
    {
        $booking = $this->makeBooking();

        $booking->update(['status' => 'confirmed']);

        $this->assertDatabaseHas('booking_state_transitions', [
            'booking_id' => $booking->id,
            'from_state' => 'pending',
            'to_state' => 'confirmed',
        ]);
    }

    public function test_invalid_transition_throws_and_is_not_logged(): void
    {
        $booking = $this->makeBooking();

        $this->expectException(InvalidArgumentException::class);

        try {
            $booking->update(['status' => 'checked_out']);
        } finally {
            $this->assertDatabaseMissing('booking_state_transitions', [
                'booking_id' => $booking->id,
                'to_state' => 'checked_out',
            ]);
        }
    }

    public function test_full_happy_path_lifecycle(): void
    {
        $booking = $this->makeBooking();

        $booking->update(['status' => 'confirmed']);
        $booking->update(['status' => 'checked_in']);
        $booking->update(['status' => 'checked_out']);

        $this->assertEquals(
            3,
            $booking->transitions()->count(),
            'Expected 3 transitions: pending->confirmed->checked_in->checked_out'
        );
    }
}
