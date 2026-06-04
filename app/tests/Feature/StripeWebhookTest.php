<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Host;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_payment_intent_succeeded_marks_booking_as_paid_and_confirmed(): void
    {
        $hostUser = User::factory()->create();
        $host = Host::factory()->create(['user_id' => $hostUser->id]);

        $guest = User::factory()->create();

        $property = Property::factory()->create([
            'host_id' => $host->id,
            'status' => 'published',
        ]);

        $booking = Booking::factory()->create([
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);

        $payload = [
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                ],
            ],
        ];

        $this->postJson(route('stripe.webhook'), $payload)
            ->assertOk()
            ->assertJson(['received' => true]);

        $booking->refresh();

        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('confirmed', $booking->status);
    }

    public function test_webhook_ignores_unknown_payment_intent(): void
    {
        $payload = [
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_unknown',
                ],
            ],
        ];

        $this->postJson(route('stripe.webhook'), $payload)
            ->assertOk()
            ->assertJson(['received' => true]);

        $this->assertDatabaseMissing('bookings', [
            'stripe_payment_intent_id' => 'pi_unknown',
        ]);
    }
}
