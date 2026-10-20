<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Host;
use App\Models\Property;
use App\Models\User;
use App\Services\BookingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class HostSubscriptionGatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeUser(string $role = 'Host'): User
    {
        /** @var User $user */
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_unsubscribed_host_is_redirected_to_subscription_page(): void
    {
        $user = $this->makeUser();
        Host::factory()->withoutSubscription()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $this->get(route('host.dashboard'))
            ->assertRedirect(route('host.subscription'));
    }

    public function test_subscribed_host_can_access_the_dashboard(): void
    {
        $user = $this->makeUser();
        Host::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $this->get(route('host.dashboard'))->assertOk();
    }

    public function test_unsubscribed_host_can_still_open_the_subscription_page(): void
    {
        $user = $this->makeUser();
        Host::factory()->withoutSubscription()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $this->get(route('host.subscription'))->assertOk();
    }

    public function test_guest_is_redirected_to_login_before_subscription_check(): void
    {
        $this->get(route('host.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_other_roles_get_forbidden_instead_of_subscription_redirect(): void
    {
        $user = $this->makeUser('Provider');
        $this->actingAs($user);

        $this->get(route('host.dashboard'))->assertForbidden();
    }

    public function test_grace_period_subscription_still_grants_access(): void
    {
        $user = $this->makeUser();
        Host::factory()->unsubscribed()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $this->get(route('host.dashboard'))->assertOk();
    }

    public function test_expired_subscription_is_denied(): void
    {
        $user = $this->makeUser();
        $host = Host::factory()->create(['user_id' => $user->id]);

        $host->user->subscriptions()->update([
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($user);

        $this->get(route('host.dashboard'))
            ->assertRedirect(route('host.subscription'));
    }

    public function test_properties_of_unsubscribed_host_are_not_public(): void
    {
        $user = $this->makeUser();
        $host = Host::factory()->withoutSubscription()->create(['user_id' => $user->id]);

        $property = Property::factory()->create([
            'host_id' => $host->id,
            'status' => 'published',
        ]);

        $this->assertFalse(
            Property::query()->publiclyVisible()->whereKey($property->id)->exists()
        );

        $property->host->user->subscriptions()->create([
            'type' => 'host',
            'stripe_id' => 'sub_reactivated',
            'stripe_status' => 'active',
            'stripe_price' => 'price_host_standard',
            'quantity' => 1,
        ]);

        $this->assertTrue(
            Property::query()->publiclyVisible()->whereKey($property->id)->exists()
        );
    }

    public function test_unsubscribed_host_cannot_receive_new_bookings(): void
    {
        $user = $this->makeUser();
        $host = Host::factory()->withoutSubscription()->create(['user_id' => $user->id]);
        $property = Property::factory()->create([
            'host_id' => $host->id,
            'status' => 'published',
        ]);

        $this->expectException(RuntimeException::class);

        app(BookingService::class)->create($property->id, $this->bookingAttributes());
    }

    public function test_subscribed_host_can_receive_new_bookings(): void
    {
        $user = $this->makeUser();
        $host = Host::factory()->create(['user_id' => $user->id]);
        $property = Property::factory()->create([
            'host_id' => $host->id,
            'status' => 'published',
        ]);

        $booking = app(BookingService::class)->create($property->id, $this->bookingAttributes());

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertDatabaseHas('bookings', [
            'property_id' => $property->id,
            'status' => 'pending',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingAttributes(): array
    {
        return [
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
        ];
    }
}
