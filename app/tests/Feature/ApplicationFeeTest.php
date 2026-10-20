<?php

namespace Tests\Feature;

use App\Models\Host;
use App\Models\Plan;
use App\Models\Property;
use App\Models\User;
use App\Services\BookingPaymentService;
use App\Services\BookingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\StripeClient;
use Tests\TestCase;

class ApplicationFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function subscribedHost(float $platformFeePct = 15, ?string $stripeAccount = 'acct_host_1'): Host
    {
        /** @var User $user */
        $user = User::factory()->create(['stripe_id' => 'cus_host_1']);
        $user->assignRole('Host');

        $plan = Plan::factory()->create([
            'stripe_price_id' => 'price_fee',
            'price' => 99,
            'interval' => 'month',
            'platform_fee_pct' => $platformFeePct,
        ]);

        $user->subscriptions()->create([
            'type' => 'host',
            'stripe_id' => 'sub_host_billing',
            'stripe_status' => 'active',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
        ]);

        return Host::factory()->create([
            'user_id' => $user->id,
            'stripe_account_id' => $stripeAccount,
        ]);
    }

    /** @var array<string, mixed> */
    private array $captured = [];

    /**
     * @return array{0: StripeClient, 1: array<string, mixed>}
     */
    private function fakeStripe(): array
    {
        $this->captured = [];

        $customers = Mockery::mock();
        $customers->shouldReceive('retrieve')
            ->andReturnUsing(fn (string $id): object => (object) ['id' => $id]);

        $paymentIntents = Mockery::mock();
        $paymentIntents->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $params): bool {
                $this->captured = $params;

                return true;
            }))
            ->andReturn((object) ['id' => 'pi_test_1']);

        $client = Mockery::mock(StripeClient::class);
        $client->customers = $customers;
        $client->paymentIntents = $paymentIntents;

        // Cashier::stripe() resolves with contextual parameters, which bypasses
        // instance() bindings, so the mock is registered as a buildable binding.
        $this->app->bind(StripeClient::class, fn () => $client);

        return [$client, $this->captured];
    }

    public function test_payment_intent_collects_the_plan_application_fee_and_transfers_to_the_host(): void
    {
        $host = $this->subscribedHost();
        $property = Property::factory()->create(['host_id' => $host->id, 'status' => 'published']);
        $guest = User::factory()->create(['stripe_id' => 'cus_guest_1']);

        $this->fakeStripe();

        $booking = app(BookingService::class)->create($property->id, [
            'guest_id' => $guest->id,
            'guest_name' => 'Ion Popescu',
            'guest_email' => 'ion@example.test',
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-05',
            'nights' => 4,
            'nightly_rate' => 250,
            'subtotal' => 1000,
            'cleaning_fee' => 0,
            'platform_fee' => 150,
            'host_payout' => 850,
            'total_price' => 1000,
            'currency' => 'RON',
            'status' => 'pending',
            'source' => 'direct',
            'payment_status' => 'pending',
        ]);

        $this->assertSame('pi_test_1', $booking->stripe_payment_intent_id);
        $this->assertSame(100000, $this->captured['amount']);
        $this->assertSame('ron', $this->captured['currency']);
        $this->assertSame('cus_guest_1', $this->captured['customer']);
        $this->assertSame(15000, $this->captured['application_fee_amount']);
        $this->assertSame(['destination' => 'acct_host_1'], $this->captured['transfer_data']);
    }

    public function test_no_application_fee_or_transfer_without_a_connected_account(): void
    {
        $host = $this->subscribedHost(stripeAccount: null);
        $property = Property::factory()->create(['host_id' => $host->id, 'status' => 'published']);
        $guest = User::factory()->create(['stripe_id' => 'cus_guest_2']);

        $this->fakeStripe();

        app(BookingService::class)->create($property->id, [
            'guest_id' => $guest->id,
            'guest_name' => 'Ion Popescu',
            'guest_email' => 'ion@example.test',
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-05',
            'nights' => 2,
            'nightly_rate' => 100,
            'subtotal' => 200,
            'cleaning_fee' => 0,
            'platform_fee' => 0,
            'host_payout' => 200,
            'total_price' => 200,
            'currency' => 'RON',
            'status' => 'pending',
            'source' => 'direct',
            'payment_status' => 'pending',
        ]);

        $this->assertArrayNotHasKey('application_fee_amount', $this->captured);
        $this->assertArrayNotHasKey('transfer_data', $this->captured);
    }

    public function test_fee_falls_back_to_the_configured_platform_percentage(): void
    {
        $host = Host::factory()->withoutSubscription()->create([
            'stripe_account_id' => 'acct_host_2',
        ]);
        $property = Property::factory()->create(['host_id' => $host->id]);

        $service = app(BookingPaymentService::class);

        $expected = (int) round(10000 * (float) config('services.stripe.platform_fee_pct') / 100);

        $this->assertSame($expected, $service->applicationFeeAmount($property, 10000));
    }

    public function test_fee_is_zero_without_a_plan_percentage(): void
    {
        $host = $this->subscribedHost(platformFeePct: 0);
        $property = Property::factory()->create(['host_id' => $host->id]);

        $service = app(BookingPaymentService::class);

        $this->assertSame(0, $service->applicationFeeAmount($property, 10000));
    }
}
