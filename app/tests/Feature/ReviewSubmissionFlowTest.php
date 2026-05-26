<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Host;
use App\Models\Property;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ReviewSubmissionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function checkedOutBooking(array $overrides = []): Booking
    {
        $host = Host::factory()->create();
        $property = Property::factory()->for($host)->create([
            'status' => 'published',
            'base_price' => 200,
            'cleaning_fee' => 50,
            'currency' => 'RON',
            'max_guests' => 4,
            'rating_avg' => 0,
            'reviews_count' => 0,
        ]);
        $guest = User::factory()->create();
        $guest->assignRole('Guest');

        return Booking::factory()->create(array_merge([
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'status' => 'checked_out',
            'payment_status' => 'paid',
            'source' => 'direct',
        ], $overrides));
    }

    public function test_guest_must_login_to_access_review_page(): void
    {
        $booking = $this->checkedOutBooking();

        $this->get(route('bookings.review', $booking))
            ->assertRedirect(route('login'));
    }

    public function test_only_booking_guest_can_see_review_page(): void
    {
        $booking = $this->checkedOutBooking();
        $other = User::factory()->create();
        $other->assignRole('Guest');

        $this->actingAs($other)
            ->get(route('bookings.review', $booking))
            ->assertForbidden();
    }

    public function test_cannot_review_booking_that_is_not_checked_out(): void
    {
        $booking = $this->checkedOutBooking(['status' => 'pending']);
        $guest = $booking->guest;

        $this->actingAs($guest)
            ->get(route('bookings.review', $booking))
            ->assertForbidden();
    }

    public function test_guest_can_submit_review_and_property_aggregates_update(): void
    {
        $booking = $this->checkedOutBooking();
        $guest = $booking->guest;
        $property = $booking->property;

        $this->actingAs($guest);

        Volt::test('bookings.review', ['booking' => $booking])
            ->set('rating', 5)
            ->set('cleanliness', 4)
            ->set('communication', 5)
            ->set('accuracy', 4)
            ->set('value', 5)
            ->set('comment', 'Apartament excelent, recomand!')
            ->call('save')
            ->assertHasNoErrors();

        $review = Review::query()->where('booking_id', $booking->id)->first();
        $this->assertNotNull($review);
        $this->assertSame($guest->id, (int) $review->author_id);
        $this->assertSame(Property::class, $review->target_type);
        $this->assertSame($property->id, (int) $review->target_id);
        $this->assertSame(5, (int) $review->rating);
        $this->assertTrue((bool) $review->is_published);

        $property->refresh();
        $this->assertSame(1, (int) $property->reviews_count);
        $this->assertEquals(5.0, (float) $property->rating_avg);
    }

    public function test_duplicate_review_is_blocked(): void
    {
        $booking = $this->checkedOutBooking();
        $guest = $booking->guest;

        Review::create([
            'booking_id' => $booking->id,
            'author_id' => $guest->id,
            'target_type' => Property::class,
            'target_id' => $booking->property_id,
            'rating' => 4,
            'is_published' => true,
        ]);

        $this->actingAs($guest)
            ->get(route('bookings.review', $booking))
            ->assertForbidden();
    }

    public function test_rating_must_be_within_one_to_five(): void
    {
        $booking = $this->checkedOutBooking();
        $guest = $booking->guest;
        $this->actingAs($guest);

        Volt::test('bookings.review', ['booking' => $booking])
            ->set('rating', 7)
            ->call('save')
            ->assertHasErrors(['rating']);

        $this->assertSame(0, Review::query()->count());
    }

    public function test_review_cta_appears_on_booking_show_after_checkout(): void
    {
        $booking = $this->checkedOutBooking();
        $guest = $booking->guest;

        $this->actingAs($guest)
            ->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertSee('Lasă un review');
    }
}
