<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Host;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use Tests\TestCase;

class HostBookingTransitionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Mail::fake();
    }

    private function actAsHost(): array
    {
        /** @var User $user */
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $user->assignRole('Host');
        $host = Host::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        return [$user, $host];
    }

    private function makeBookingForHost(Host $host, array $state = []): Booking
    {
        $property = Property::factory()->for($host)->create(['status' => 'published']);

        return Booking::factory()->for($property)->create(array_merge([
            'status' => 'pending',
            'payment_status' => 'pending',
        ], $state));
    }

    // ── Page access ──────────────────────────────────────────────────────────

    public function test_guest_cannot_access_host_bookings_page(): void
    {
        $this->get(route('host.bookings'))->assertRedirect(route('login'));
    }

    public function test_non_host_user_cannot_access_host_bookings_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Guest');
        $this->actingAs($user);

        $this->get(route('host.bookings'))->assertForbidden();
    }

    public function test_host_sees_only_their_bookings(): void
    {
        [, $host] = $this->actAsHost();
        $myBooking = $this->makeBookingForHost($host, ['guest_name' => 'Guest Propriu']);

        $otherHost = Host::factory()->create();
        $this->makeBookingForHost($otherHost, ['guest_name' => 'Guest Strain']);

        Volt::test('host.bookings')
            ->assertSee('Guest Propriu')
            ->assertDontSee('Guest Strain');
    }

    // ── Confirm ──────────────────────────────────────────────────────────────

    public function test_host_can_confirm_pending_booking(): void
    {
        [, $host] = $this->actAsHost();
        $booking = $this->makeBookingForHost($host, ['status' => 'pending']);

        Volt::test('host.bookings')
            ->call('confirm', $booking->id);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_host_cannot_confirm_booking_of_another_host(): void
    {
        [, $host] = $this->actAsHost();
        $otherHost = Host::factory()->create();
        $booking = $this->makeBookingForHost($otherHost, ['status' => 'pending']);

        $this->withoutExceptionHandling();
        $this->expectException(ModelNotFoundException::class);

        Volt::test('host.bookings')
            ->call('confirm', $booking->id);
    }

    // ── Cancel ───────────────────────────────────────────────────────────────

    public function test_host_can_cancel_pending_booking_with_reason(): void
    {
        [, $host] = $this->actAsHost();
        $booking = $this->makeBookingForHost($host, ['status' => 'pending']);

        Volt::test('host.bookings')
            ->call('openCancel', $booking->id)
            ->set('cancelReason', 'Proprietatea indisponibilă')
            ->call('cancelBooking');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Proprietatea indisponibilă',
        ]);
    }

    public function test_host_can_cancel_confirmed_booking(): void
    {
        [, $host] = $this->actAsHost();
        $booking = $this->makeBookingForHost($host, ['status' => 'confirmed']);

        Volt::test('host.bookings')
            ->call('openCancel', $booking->id)
            ->call('cancelBooking');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    // ── Check-in ─────────────────────────────────────────────────────────────

    public function test_host_can_check_in_confirmed_booking(): void
    {
        [, $host] = $this->actAsHost();
        $booking = $this->makeBookingForHost($host, ['status' => 'confirmed']);

        Volt::test('host.bookings')
            ->call('checkIn', $booking->id);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'checked_in',
        ]);
    }

    public function test_host_cannot_check_in_pending_booking(): void
    {
        [, $host] = $this->actAsHost();
        $booking = $this->makeBookingForHost($host, ['status' => 'pending']);

        $this->expectException(\DomainException::class);

        Volt::test('host.bookings')
            ->call('checkIn', $booking->id);
    }

    // ── Check-out ────────────────────────────────────────────────────────────

    public function test_host_can_check_out_checked_in_booking(): void
    {
        [, $host] = $this->actAsHost();
        $booking = $this->makeBookingForHost($host, ['status' => 'checked_in']);

        Volt::test('host.bookings')
            ->call('checkOut', $booking->id);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'checked_out',
        ]);
    }

    public function test_host_cannot_check_out_pending_booking(): void
    {
        [, $host] = $this->actAsHost();
        $booking = $this->makeBookingForHost($host, ['status' => 'pending']);

        $this->expectException(\DomainException::class);

        Volt::test('host.bookings')
            ->call('checkOut', $booking->id);
    }
}
