<?php

namespace Tests\Feature;

use App\Models\Host;
use App\Models\User;
use App\Services\StripeConnectService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Mockery;
use Stripe\StripeClient;
use Tests\TestCase;

class HostStripeConnectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actAsHost(array $hostAttrs = []): array
    {
        /** @var User $user */
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $user->assignRole('Host');
        $host = Host::factory()->create(array_merge(['user_id' => $user->id], $hostAttrs));
        $this->actingAs($user);

        return [$user, $host];
    }

    // ── Access ────────────────────────────────────────────────────────────────

    public function test_host_can_view_connect_page(): void
    {
        $this->actAsHost();

        Volt::test('host.connect')
            ->assertOk();
    }

    public function test_guest_cannot_access_connect_page(): void
    {
        $this->get(route('host.connect'))
            ->assertRedirect(route('login'));
    }

    // ── startOnboarding — new account ─────────────────────────────────────────

    public function test_start_onboarding_creates_account_and_redirects(): void
    {
        [, $host] = $this->actAsHost();

        $mock = Mockery::mock(StripeConnectService::class);
        $mock->shouldReceive('createAccount')
            ->once()
            ->andReturn($host->fill(['stripe_account_id' => 'acct_test123']));
        $mock->shouldReceive('createOnboardingLink')
            ->once()
            ->andReturn('https://connect.stripe.com/setup/test');

        $this->app->instance(StripeConnectService::class, $mock);

        Volt::test('host.connect')
            ->call('startOnboarding')
            ->assertSet('errorMessage', null);
    }

    public function test_start_onboarding_skips_create_account_if_already_exists(): void
    {
        [, $host] = $this->actAsHost([
            'stripe_account_id' => 'acct_existing',
            'stripe_account_status' => 'pending',
        ]);

        $mock = Mockery::mock(StripeConnectService::class);
        $mock->shouldNotReceive('createAccount');
        $mock->shouldReceive('createOnboardingLink')
            ->once()
            ->andReturn('https://connect.stripe.com/setup/continue');

        $this->app->instance(StripeConnectService::class, $mock);

        Volt::test('host.connect')
            ->call('startOnboarding')
            ->assertSet('errorMessage', null);
    }

    public function test_start_onboarding_sets_error_message_on_stripe_exception(): void
    {
        $this->actAsHost();

        $mock = Mockery::mock(StripeConnectService::class);
        $mock->shouldReceive('createAccount')
            ->once()
            ->andThrow(new \RuntimeException('Stripe error'));

        $this->app->instance(StripeConnectService::class, $mock);

        Volt::test('host.connect')
            ->call('startOnboarding')
            ->assertSet('errorMessage', 'Eroare Stripe: Stripe error');
    }

    // ── refreshStatus ─────────────────────────────────────────────────────────

    public function test_refresh_status_calls_service_and_clears_error(): void
    {
        [, $host] = $this->actAsHost([
            'stripe_account_id' => 'acct_existing',
            'stripe_account_status' => 'pending',
        ]);

        $mock = Mockery::mock(StripeConnectService::class);
        $mock->shouldReceive('refreshAccountStatus')
            ->once()
            ->andReturn($host->fill(['stripe_account_status' => 'active']));

        $this->app->instance(StripeConnectService::class, $mock);

        Volt::test('host.connect')
            ->set('errorMessage', 'old error')
            ->call('refreshStatus')
            ->assertSet('errorMessage', null);
    }

    // ── Return route ──────────────────────────────────────────────────────────

    public function test_return_route_refreshes_status_and_redirects_to_connect(): void
    {
        [, $host] = $this->actAsHost(['stripe_account_id' => 'acct_existing']);

        $mock = Mockery::mock(StripeConnectService::class);
        $mock->shouldReceive('refreshAccountStatus')
            ->once()
            ->with(Mockery::on(fn ($h) => $h instanceof Host && $h->id === $host->id));

        $this->app->instance(StripeConnectService::class, $mock);

        $this->get(route('host.connect.return'))
            ->assertRedirect(route('host.connect'));
    }

    // ── StripeConnectService unit tests (with StripeClient mock) ──────────────

    public function test_service_create_account_saves_stripe_id_and_sets_pending(): void
    {
        [, $host] = $this->actAsHost();

        $fakeAccount = (object) ['id' => 'acct_new_123'];

        $stripeAccounts = Mockery::mock();
        $stripeAccounts->shouldReceive('create')->once()->andReturn($fakeAccount);

        $stripeClient = Mockery::mock(StripeClient::class);
        $stripeClient->accounts = $stripeAccounts;

        $service = new StripeConnectService($stripeClient);
        $result = $service->createAccount($host);

        $this->assertEquals('acct_new_123', $result->stripe_account_id);
        $this->assertEquals('pending', $result->stripe_account_status);
    }

    public function test_service_create_account_is_idempotent_if_id_exists(): void
    {
        [, $host] = $this->actAsHost(['stripe_account_id' => 'acct_already']);

        $stripeClient = Mockery::mock(StripeClient::class);
        // Should NOT call accounts->create
        $stripeClient->shouldNotReceive('accounts');

        $service = new StripeConnectService($stripeClient);
        $result = $service->createAccount($host);

        $this->assertEquals('acct_already', $result->stripe_account_id);
    }

    public function test_service_refresh_sets_active_when_charges_enabled(): void
    {
        [, $host] = $this->actAsHost(['stripe_account_id' => 'acct_xyz']);

        $fakeAccount = (object) [
            'charges_enabled' => true,
            'details_submitted' => true,
        ];

        $stripeAccounts = Mockery::mock();
        $stripeAccounts->shouldReceive('retrieve')->once()->andReturn($fakeAccount);

        $stripeClient = Mockery::mock(StripeClient::class);
        $stripeClient->accounts = $stripeAccounts;

        $service = new StripeConnectService($stripeClient);
        $result = $service->refreshAccountStatus($host);

        $this->assertEquals('active', $result->stripe_account_status);
        $this->assertNotNull($result->onboarded_at);
    }

    public function test_service_refresh_sets_restricted_when_details_submitted_but_not_active(): void
    {
        [, $host] = $this->actAsHost(['stripe_account_id' => 'acct_xyz']);

        $fakeAccount = (object) [
            'charges_enabled' => false,
            'details_submitted' => true,
        ];

        $stripeAccounts = Mockery::mock();
        $stripeAccounts->shouldReceive('retrieve')->once()->andReturn($fakeAccount);

        $stripeClient = Mockery::mock(StripeClient::class);
        $stripeClient->accounts = $stripeAccounts;

        $service = new StripeConnectService($stripeClient);
        $result = $service->refreshAccountStatus($host);

        $this->assertEquals('restricted', $result->stripe_account_status);
    }

    public function test_service_create_onboarding_link_returns_url(): void
    {
        [, $host] = $this->actAsHost(['stripe_account_id' => 'acct_test']);

        $fakeLink = (object) ['url' => 'https://connect.stripe.com/setup/start'];

        $stripeAccountLinks = Mockery::mock();
        $stripeAccountLinks->shouldReceive('create')->once()->andReturn($fakeLink);

        $stripeClient = Mockery::mock(StripeClient::class);
        $stripeClient->accountLinks = $stripeAccountLinks;

        $service = new StripeConnectService($stripeClient);
        $url = $service->createOnboardingLink($host, 'https://app.test/return', 'https://app.test/refresh');

        $this->assertEquals('https://connect.stripe.com/setup/start', $url);
    }
}
