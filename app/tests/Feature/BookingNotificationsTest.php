<?php

namespace Tests\Feature;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Host;
use App\Models\Property;
use App\Models\User;
use App\Services\BookingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Mail::fake();
    }

    private function pendingBooking(): Booking
    {
        $host = Host::factory()->create();
        $property = Property::factory()->for($host)->create([
            'status' => 'published',
            'base_price' => 200,
            'cleaning_fee' => 50,
            'currency' => 'RON',
        ]);
        $guest = User::factory()->create(['email' => 'guest@example.test']);

        return Booking::factory()->create([
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'guest_email' => 'guest@example.test',
            'status' => 'pending',
            'payment_status' => 'pending',
            'source' => 'direct',
        ]);
    }

    public function test_confirming_booking_sends_email_to_guest(): void
    {
        $booking = $this->pendingBooking();
        $service = app(BookingService::class);

        $confirmed = $service->confirm($booking);

        $this->assertSame('confirmed', $confirmed->status);
        Mail::assertSent(BookingConfirmedMail::class, function (BookingConfirmedMail $mail) use ($booking) {
            return $mail->booking->id === $booking->id
                && $mail->hasTo('guest@example.test');
        });
    }

    public function test_cannot_confirm_already_cancelled_booking(): void
    {
        $booking = $this->pendingBooking();
        $booking->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $service = app(BookingService::class);

        $this->expectException(\DomainException::class);
        try {
            $service->confirm($booking);
        } finally {
            Mail::assertNothingSent();
        }
    }

    public function test_cancelling_booking_sends_email_with_reason(): void
    {
        $booking = $this->pendingBooking();
        $service = app(BookingService::class);

        $cancelled = $service->cancel($booking, 'Plata neefectuată');

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertNotNull($cancelled->cancelled_at);
        $this->assertSame('Plata neefectuată', $cancelled->cancellation_reason);
        Mail::assertSent(BookingCancelledMail::class, function (BookingCancelledMail $mail) use ($booking) {
            return $mail->booking->id === $booking->id
                && $mail->reason === 'Plata neefectuată'
                && $mail->hasTo('guest@example.test');
        });
    }

    public function test_cannot_cancel_checked_out_booking(): void
    {
        $booking = $this->pendingBooking();
        // Tranzitii valide: pending -> confirmed -> checked_in -> checked_out.
        $booking->update(['status' => 'confirmed']);
        $booking->update(['status' => 'checked_in']);
        $booking->update(['status' => 'checked_out']);
        $service = app(BookingService::class);

        $this->expectException(\DomainException::class);
        try {
            $service->cancel($booking, 'orice');
        } finally {
            Mail::assertNothingSent();
        }
    }

    public function test_confirming_without_guest_email_does_not_send_mail(): void
    {
        $booking = $this->pendingBooking();
        $booking->update(['guest_email' => null]);
        $service = app(BookingService::class);

        $service->confirm($booking);

        Mail::assertNothingSent();
        $this->assertSame('confirmed', $booking->fresh()->status);
    }
}
