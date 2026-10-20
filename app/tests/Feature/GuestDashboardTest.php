<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Host;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class GuestDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function guest(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Guest');

        return $user;
    }

    private function pubishedProperty(): Property
    {
        $host = Host::factory()->create();

        return Property::factory()->for($host)->create(['status' => 'published']);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_shows_welcome_and_empty_state(): void
    {
        $guest = $this->guest();

        $this->actingAs($guest)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Bun venit, '.$guest->name)
            ->assertSee('dashboard-empty-bookings', false);
    }

    public function test_dashboard_lists_upcoming_bookings(): void
    {
        $guest = $this->guest();
        $property = $this->pubishedProperty();

        $booking = Booking::factory()->create([
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'source' => 'direct',
        ]);

        $this->actingAs($guest);

        Volt::test('dashboard')
            ->assertSee($property->title)
            ->assertSee($booking->reference);

        $this->actingAs($guest)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('dashboard-booking-'.$booking->reference, false)
            ->assertSee('Confirmată');
    }

    public function test_dashboard_shows_past_bookings(): void
    {
        $guest = $this->guest();
        $property = $this->pubishedProperty();

        Booking::factory()->create([
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'status' => 'checked_out',
            'payment_status' => 'paid',
            'source' => 'direct',
        ]);

        $this->actingAs($guest)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Istoric recent')
            ->assertSee('Finalizată');
    }

    public function test_dashboard_does_not_show_other_guests_bookings(): void
    {
        $guest = $this->guest();
        $other = $this->guest();
        $property = $this->pubishedProperty();

        $booking = Booking::factory()->create([
            'property_id' => $property->id,
            'guest_id' => $other->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'source' => 'direct',
        ]);

        $this->actingAs($guest)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee($booking->reference);
    }

    public function test_dashboard_shows_profile_completion_hint(): void
    {
        $guest = $this->guest();

        $this->actingAs($guest)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('profil completat')
            ->assertSee('Completează-ți profilul');
    }
}
