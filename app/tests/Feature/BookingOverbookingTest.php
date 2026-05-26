<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Property;
use App\Services\BookingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BookingOverbookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_first_booking_is_created_successfully(): void
    {
        $property = Property::factory()->create();
        $service = app(BookingService::class);

        $booking = $service->create($property->id, [
            'guest_name' => 'Ion Popescu',
            'guest_email' => 'ion@example.test',
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-05',
            'nights' => 4,
            'nightly_rate' => 200,
            'subtotal' => 800,
            'cleaning_fee' => 80,
            'platform_fee' => 80,
            'host_payout' => 720,
            'total_price' => 960,
            'currency' => 'RON',
            'status' => 'pending',
            'source' => 'direct',
            'payment_status' => 'pending',
        ]);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_overlapping_booking_is_rejected(): void
    {
        $property = Property::factory()->create();
        Booking::factory()->confirmed()->create([
            'property_id' => $property->id,
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-05',
        ]);

        $service = app(BookingService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Overbooking refuzat');

        $service->create($property->id, [
            'guest_name' => 'Maria Ionescu',
            'guest_email' => 'maria@example.test',
            'check_in' => '2026-08-03', // suprapunere cu 2026-08-01..2026-08-05
            'check_out' => '2026-08-07',
            'nights' => 4,
            'nightly_rate' => 200,
            'subtotal' => 800,
            'cleaning_fee' => 80,
            'platform_fee' => 80,
            'host_payout' => 720,
            'total_price' => 960,
            'currency' => 'RON',
            'status' => 'pending',
            'source' => 'direct',
            'payment_status' => 'pending',
        ]);
    }

    public function test_adjacent_booking_is_allowed(): void
    {
        // check_out al unei rezervări == check_in al următoarei → fără overlap
        $property = Property::factory()->create();
        Booking::factory()->confirmed()->create([
            'property_id' => $property->id,
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-05',
        ]);

        $service = app(BookingService::class);

        $b2 = $service->create($property->id, [
            'guest_name' => 'Adi Pop',
            'guest_email' => 'adi@example.test',
            'check_in' => '2026-08-05',
            'check_out' => '2026-08-08',
            'nights' => 3,
            'nightly_rate' => 200,
            'subtotal' => 600,
            'cleaning_fee' => 80,
            'platform_fee' => 60,
            'host_payout' => 540,
            'total_price' => 740,
            'currency' => 'RON',
            'status' => 'pending',
            'source' => 'direct',
            'payment_status' => 'pending',
        ]);

        $this->assertDatabaseCount('bookings', 2);
        $this->assertEquals('2026-08-05', $b2->check_in->format('Y-m-d'));
    }

    public function test_cancelled_booking_does_not_block_new_booking(): void
    {
        $property = Property::factory()->create();
        Booking::factory()->create([
            'property_id' => $property->id,
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-05',
            'status' => 'cancelled',
        ]);

        $service = app(BookingService::class);

        $booking = $service->create($property->id, [
            'guest_name' => 'Test',
            'guest_email' => 'test@example.test',
            'check_in' => '2026-08-02',
            'check_out' => '2026-08-04',
            'nights' => 2,
            'nightly_rate' => 200,
            'subtotal' => 400,
            'cleaning_fee' => 80,
            'platform_fee' => 40,
            'host_payout' => 360,
            'total_price' => 520,
            'currency' => 'RON',
            'status' => 'pending',
            'source' => 'direct',
            'payment_status' => 'pending',
        ]);

        $this->assertDatabaseCount('bookings', 2);
    }
}
