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

class PublicBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function publishedProperty(array $overrides = []): Property
    {
        $host = Host::factory()->create();

        return Property::factory()->for($host)->create(array_merge([
            'status' => 'published',
            'base_price' => 200,
            'cleaning_fee' => 50,
            'currency' => 'RON',
            'max_guests' => 4,
        ], $overrides));
    }

    public function test_guest_must_login_to_access_book_page(): void
    {
        $property = $this->publishedProperty();

        $this->get(route('properties.book', $property))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_book_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Guest');
        $property = $this->publishedProperty();

        $this->actingAs($user)
            ->get(route('properties.book', $property))
            ->assertOk()
            ->assertSee('Rezervă');
    }

    public function test_authenticated_user_can_create_booking(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Guest');
        $property = $this->publishedProperty();

        $checkIn = now()->addDays(5)->toDateString();
        $checkOut = now()->addDays(8)->toDateString();

        $this->actingAs($user);

        Volt::test('properties.book', ['property' => $property])
            ->set('checkIn', $checkIn)
            ->set('checkOut', $checkOut)
            ->set('guestsCount', 2)
            ->call('save')
            ->assertHasNoErrors();

        $booking = Booking::query()->where('guest_id', $user->id)->first();

        $this->assertNotNull($booking);
        $this->assertSame($property->id, $booking->property_id);
        $this->assertSame(3, (int) $booking->nights);
        $this->assertEquals(600.00, (float) $booking->subtotal);
        $this->assertEquals(650.00, (float) $booking->total_price);
        $this->assertSame('pending', $booking->status);
    }

    public function test_booking_creation_rejects_overlap(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Guest');
        $property = $this->publishedProperty();

        $existingIn = now()->addDays(30)->toDateString();
        $existingOut = now()->addDays(35)->toDateString();

        // Existing pending booking blocks the interval.
        Booking::factory()->create([
            'property_id' => $property->id,
            'guest_id' => null,
            'check_in' => $existingIn,
            'check_out' => $existingOut,
            'status' => 'pending',
        ]);

        $this->actingAs($user);

        $component = Volt::test('properties.book', ['property' => $property])
            ->set('checkIn', now()->addDays(32)->toDateString())
            ->set('checkOut', now()->addDays(34)->toDateString())
            ->set('guestsCount', 1)
            ->call('save');

        $component->assertSet('errorMessage', fn ($v) => is_string($v) && str_contains($v, 'suprapune'));

        $this->assertSame(1, Booking::query()->where('property_id', $property->id)->count());
    }

    public function test_booking_show_visible_to_guest_owner(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Guest');
        $property = $this->publishedProperty();

        $booking = Booking::factory()->create([
            'property_id' => $property->id,
            'guest_id' => $user->id,
            'reference' => 'BK-OWNERVIS',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertSee('BK-OWNERVIS')
            ->assertSee('pending');
    }

    public function test_booking_show_forbidden_to_other_user(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $property = $this->publishedProperty();

        $booking = Booking::factory()->create([
            'property_id' => $property->id,
            'guest_id' => $owner->id,
            'reference' => 'BK-PRIVATE1',
            'status' => 'pending',
        ]);

        $this->actingAs($intruder)
            ->get(route('bookings.show', $booking))
            ->assertForbidden();
    }
}
