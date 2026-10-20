<?php

namespace Tests\Feature;

use App\Events\BookingCheckedOut;
use App\Models\Booking;
use App\Models\Host;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CheckoutCleaningTaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makePropertyWithBooking(Host $host, array $propertyState = [], array $bookingState = []): array
    {
        $property = Property::factory()->for($host)->create(array_merge(['status' => 'published'], $propertyState));
        $booking = Booking::factory()->for($property)->create(array_merge([
            'status' => 'checked_in',
            'payment_status' => 'paid',
        ], $bookingState));

        return [$property, $booking];
    }

    private function actAsHost(): Host
    {
        /** @var User $user */
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $user->assignRole('Host');
        $host = Host::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        return $host;
    }

    // ── Eveniment & declanșare ───────────────────────────────────────────────

    public function test_check_out_dispatches_booking_checked_out_event(): void
    {
        Event::fake([BookingCheckedOut::class]);

        $host = $this->actAsHost();
        [, $booking] = $this->makePropertyWithBooking($host);

        Volt::test('host.bookings')
            ->call('checkOut', $booking->id);

        Event::assertDispatched(BookingCheckedOut::class, fn (BookingCheckedOut $event): bool => $event->booking->is($booking));
    }

    public function test_check_out_creates_open_cleaning_task_when_no_provider_available(): void
    {
        $host = $this->actAsHost();
        [$property, $booking] = $this->makePropertyWithBooking($host);

        Volt::test('host.bookings')
            ->call('checkOut', $booking->id);

        $task = Task::query()->where('booking_id', $booking->id)->firstOrFail();

        $this->assertSame('cleaning', $task->type);
        $this->assertSame('open', $task->status);
        $this->assertNull($task->provider_id);
        $this->assertSame($property->id, $task->property_id);
        $this->assertNotNull($task->scheduled_at);
    }

    // ── Reguli de alocare ─────────────────────────────────────────────────────

    public function test_task_assigned_to_property_preferred_provider(): void
    {
        $host = $this->actAsHost();
        $provider = Provider::factory()->create(['is_active' => true, 'kyc_status' => 'approved']);
        [$property, $booking] = $this->makePropertyWithBooking($host, [
            'preferred_provider_id' => $provider->id,
        ]);

        Volt::test('host.bookings')
            ->call('checkOut', $booking->id);

        $task = Task::query()->where('booking_id', $booking->id)->firstOrFail();

        $this->assertSame($provider->id, $task->provider_id);
        $this->assertSame('assigned', $task->status);
        $this->assertSame($property->preferred_provider_id, $task->provider_id);
    }

    public function test_inactive_or_unapproved_preferred_provider_falls_back_to_city_match(): void
    {
        $host = $this->actAsHost();
        $inactive = Provider::factory()->create([
            'is_active' => false,
            'kyc_status' => 'approved',
            'coverage_cities' => ['Bucuresti'],
        ]);
        $cityProvider = Provider::factory()->create([
            'is_active' => true,
            'kyc_status' => 'approved',
            'service_types' => ['cleaning'],
            'coverage_cities' => ['Bucuresti'],
        ]);
        $property = Property::factory()->for($host)->create([
            'status' => 'published',
            'city' => 'Bucuresti',
            'preferred_provider_id' => $inactive->id,
        ]);
        $booking = Booking::factory()->for($property)->create(['status' => 'checked_in']);

        Volt::test('host.bookings')
            ->call('checkOut', $booking->id);

        $task = Task::query()->where('booking_id', $booking->id)->firstOrFail();

        $this->assertSame($cityProvider->id, $task->provider_id);
        $this->assertSame('assigned', $task->status);
    }

    public function test_fallback_matches_city_case_insensitively_and_requires_cleaning_service(): void
    {
        $host = $this->actAsHost();
        $repairOnly = Provider::factory()->create([
            'is_active' => true,
            'kyc_status' => 'approved',
            'service_types' => ['repair'],
            'coverage_cities' => ['BRAȘOV'],
        ]);
        $cleaner = Provider::factory()->create([
            'is_active' => true,
            'kyc_status' => 'approved',
            'service_types' => ['cleaning', 'laundry'],
            'coverage_cities' => ['Brașov'],
        ]);
        $property = Property::factory()->for($host)->create([
            'status' => 'published',
            'city' => 'brașov',
        ]);
        $booking = Booking::factory()->for($property)->create(['status' => 'checked_in']);

        Volt::test('host.bookings')
            ->call('checkOut', $booking->id);

        $task = Task::query()->where('booking_id', $booking->id)->firstOrFail();

        $this->assertSame($cleaner->id, $task->provider_id);
        $this->assertNotSame($repairOnly->id, $task->provider_id);
    }

    public function test_provider_with_empty_coverage_is_matched_as_universal(): void
    {
        $host = $this->actAsHost();
        $universal = Provider::factory()->create([
            'is_active' => true,
            'kyc_status' => 'approved',
            'service_types' => ['cleaning'],
            'coverage_cities' => [],
        ]);
        $property = Property::factory()->for($host)->create(['status' => 'published']);
        $booking = Booking::factory()->for($property)->create(['status' => 'checked_in']);

        Volt::test('host.bookings')
            ->call('checkOut', $booking->id);

        $task = Task::query()->where('booking_id', $booking->id)->firstOrFail();

        $this->assertSame($universal->id, $task->provider_id);
    }

    // ── Idempotență & programare ──────────────────────────────────────────────

    public function test_duplicate_check_out_does_not_create_second_task(): void
    {
        $host = $this->actAsHost();
        [, $booking] = $this->makePropertyWithBooking($host);

        Volt::test('host.bookings')
            ->call('checkOut', $booking->id);

        $this->assertSame(1, Task::query()->where('booking_id', $booking->id)->count());

        $booking->update(['status' => 'disputed']);
        $booking->update(['status' => 'checked_out']);

        $this->assertSame(1, Task::query()->where('booking_id', $booking->id)->count());
    }

    public function test_cancelled_task_does_not_block_new_task_creation(): void
    {
        $host = $this->actAsHost();
        [, $booking] = $this->makePropertyWithBooking($host);
        Task::factory()->create([
            'property_id' => $booking->property_id,
            'booking_id' => $booking->id,
            'type' => 'cleaning',
            'status' => 'cancelled',
        ]);

        Volt::test('host.bookings')
            ->call('checkOut', $booking->id);

        $this->assertSame(2, Task::query()->where('booking_id', $booking->id)->count());
    }

    public function test_task_is_scheduled_at_property_check_out_time(): void
    {
        $host = $this->actAsHost();
        [$property, $booking] = $this->makePropertyWithBooking($host, [
            'check_out_time' => '12:30:00',
        ]);

        Volt::test('host.bookings')
            ->call('checkOut', $booking->id);

        $task = Task::query()->where('booking_id', $booking->id)->firstOrFail();

        $this->assertSame(
            $booking->check_out->format('Y-m-d').' 12:30:00',
            $task->scheduled_at->format('Y-m-d H:i:s'),
        );
        $this->assertSame($property->check_out_time, '12:30:00');
    }

    // ── Reguli de alocare ─────────────────────────────────────────────────────

    public function test_host_can_set_preferred_provider_on_property(): void
    {
        $host = $this->actAsHost();
        $provider = Provider::factory()->create(['is_active' => true, 'kyc_status' => 'approved']);
        $property = Property::factory()->for($host)->create(['status' => 'published']);

        Volt::test('host.properties.edit', ['property' => $property])
            ->set('preferredProviderId', $provider->id)
            ->call('save');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'preferred_provider_id' => $provider->id,
        ]);
    }

    public function test_host_clears_preferred_provider_with_empty_value(): void
    {
        $host = $this->actAsHost();
        $provider = Provider::factory()->create();
        $property = Property::factory()->for($host)->create([
            'status' => 'published',
            'preferred_provider_id' => $provider->id,
        ]);

        Volt::test('host.properties.edit', ['property' => $property])
            ->set('preferredProviderId', null)
            ->call('save');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'preferred_provider_id' => null,
        ]);
    }

    public function test_other_status_transitions_do_not_create_tasks(): void
    {
        $host = $this->actAsHost();
        [, $booking] = $this->makePropertyWithBooking($host, [], ['status' => 'confirmed']);

        Volt::test('host.bookings')
            ->call('checkIn', $booking->id);

        $this->assertSame(0, Task::query()->where('booking_id', $booking->id)->count());
    }
}
