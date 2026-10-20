<?php

namespace Tests\Feature;

use App\Models\Host;
use App\Models\Plan;
use App\Models\User;
use App\Services\HostSubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Mockery;
use Tests\TestCase;

class HostSubscriptionPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * @return array{0: User, 1: Host}
     */
    private function actAsHost(bool $subscribed = true): array
    {
        /** @var User $user */
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);
        $user->assignRole('Host');

        $host = Host::factory()
            ->when(! $subscribed, fn ($factory) => $factory->withoutSubscription())
            ->create(['user_id' => $user->id]);

        $this->actingAs($user);

        return [$user, $host];
    }

    public function test_page_lists_available_plans(): void
    {
        $this->actAsHost(subscribed: false);

        $plan = Plan::factory()->create([
            'name_ro' => 'Plan Premium',
            'price' => 199,
            'is_active' => true,
        ]);

        Plan::factory()->inactive()->create(['name_ro' => 'Plan Ascuns']);

        Volt::test('host.subscription')
            ->assertOk()
            ->assertSee('Plan Premium')
            ->assertDontSee('Plan Ascuns')
            ->assertSee('Aboneaza-te')
            ->assertSee('subscribe-plan-'.$plan->id);
    }

    public function test_page_shows_empty_state_without_plans(): void
    {
        $this->actAsHost();
        Plan::query()->delete();

        Volt::test('host.subscription')
            ->assertOk()
            ->assertSee('Nu exista planuri disponibile');
    }

    public function test_subscribe_redirects_to_the_stripe_checkout_url(): void
    {
        [$user] = $this->actAsHost(subscribed: false);

        $plan = Plan::factory()->create();

        $mock = Mockery::mock(HostSubscriptionService::class);
        $mock->shouldReceive('startCheckout')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => $arg instanceof User && $arg->id === $user->id),
                Mockery::on(fn ($arg) => $arg instanceof Plan && $arg->id === $plan->id),
                Mockery::type('string'),
                Mockery::type('string'),
            )
            ->andReturn('https://checkout.stripe.com/c/pay/test_session');

        $this->app->instance(HostSubscriptionService::class, $mock);

        Volt::test('host.subscription')
            ->call('subscribe', $plan->id)
            ->assertRedirect('https://checkout.stripe.com/c/pay/test_session');
    }

    public function test_subscribe_reports_error_when_plan_has_no_stripe_price(): void
    {
        $this->actAsHost(subscribed: false);

        $plan = Plan::factory()->create(['stripe_price_id' => null]);

        Volt::test('host.subscription')
            ->call('subscribe', $plan->id)
            ->assertSet('errorMessage', 'Eroare Stripe: Planul selectat nu are configurat un pret in Stripe.');
    }

    public function test_cancel_calls_the_service_and_confirms(): void
    {
        $this->actAsHost();

        $mock = Mockery::mock(HostSubscriptionService::class);
        $mock->shouldReceive('cancel')->once()->andReturn(true);

        $this->app->instance(HostSubscriptionService::class, $mock);

        Volt::test('host.subscription')
            ->call('cancel')
            ->assertSet('errorMessage', null)
            ->assertSet('infoMessage', 'Abonamentul va fi anulat la finalul perioadei curente.');
    }

    public function test_cancel_without_subscription_informs_the_host(): void
    {
        $this->actAsHost(subscribed: false);

        $mock = Mockery::mock(HostSubscriptionService::class);
        $mock->shouldReceive('cancel')->once()->andReturn(false);

        $this->app->instance(HostSubscriptionService::class, $mock);

        Volt::test('host.subscription')
            ->call('cancel')
            ->assertSet('infoMessage', 'Nu ai un abonament activ de anulat.');
    }

    public function test_success_return_from_checkout_shows_information(): void
    {
        $this->actAsHost(subscribed: false);

        $this->get(route('host.subscription', ['checkout' => 'success']))
            ->assertOk()
            ->assertSee('Abonamentul se activeaza dupa confirmarea Stripe');
    }

    public function test_page_shows_the_active_plan_name(): void
    {
        $this->actAsHost();

        Volt::test('host.subscription')
            ->assertOk()
            ->assertSee('Standard')
            ->assertSee('Anuleaza abonamentul');
    }
}
