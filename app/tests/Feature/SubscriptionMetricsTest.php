<?php

namespace Tests\Feature;

use App\Filament\Widgets\SubscriptionMetricsWidget;
use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionMetrics;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function subscribe(string $stripePrice, string $status = 'active', ?string $endsAt = null): User
    {
        /** @var User $user */
        $user = User::factory()->create(['stripe_id' => 'cus_'.uniqid()]);

        $user->subscriptions()->create([
            'type' => 'host',
            'stripe_id' => 'sub_'.uniqid(),
            'stripe_status' => $status,
            'stripe_price' => $stripePrice,
            'quantity' => 1,
            'ends_at' => $endsAt,
        ]);

        return $user;
    }

    public function test_mrr_sums_monthly_plans_and_normalises_yearly_ones(): void
    {
        Plan::factory()->create(['stripe_price_id' => 'price_monthly', 'price' => 100, 'interval' => 'month']);
        Plan::factory()->create(['stripe_price_id' => 'price_yearly', 'price' => 1200, 'interval' => 'year']);

        $this->subscribe('price_monthly');
        $this->subscribe('price_monthly');
        $this->subscribe('price_yearly');

        $metrics = app(SubscriptionMetrics::class);

        $this->assertSame(3, $metrics->activeCount());
        $this->assertSame(300.0, $metrics->mrr());
        $this->assertSame(3600.0, $metrics->arr());
    }

    public function test_metrics_ignore_non_host_subscriptions(): void
    {
        Plan::factory()->create(['stripe_price_id' => 'price_monthly', 'price' => 100]);

        /** @var User $user */
        $user = User::factory()->create();
        $user->subscriptions()->create([
            'type' => 'provider',
            'stripe_id' => 'sub_provider',
            'stripe_status' => 'active',
            'stripe_price' => 'price_monthly',
            'quantity' => 1,
        ]);

        $metrics = app(SubscriptionMetrics::class);

        $this->assertSame(0, $metrics->activeCount());
        $this->assertSame(0.0, $metrics->mrr());
    }

    public function test_metrics_ignore_unknown_prices_and_expired_subscriptions(): void
    {
        Plan::factory()->create(['stripe_price_id' => 'price_monthly', 'price' => 100]);

        $this->subscribe('price_monthly');
        $this->subscribe('price_unknown');
        $this->subscribe('price_monthly', 'canceled', now()->subDay()->toDateTimeString());

        $metrics = app(SubscriptionMetrics::class);

        $this->assertSame(2, $metrics->activeCount());
        $this->assertSame(100.0, $metrics->mrr());
    }

    public function test_cancelled_subscription_in_grace_period_still_counts(): void
    {
        Plan::factory()->create(['stripe_price_id' => 'price_monthly', 'price' => 100]);

        $this->subscribe('price_monthly', 'canceled', now()->addDays(10)->toDateTimeString());

        $metrics = app(SubscriptionMetrics::class);

        $this->assertSame(1, $metrics->activeCount());
        $this->assertSame(100.0, $metrics->mrr());
    }

    public function test_churn_rate_uses_cancellations_from_the_window(): void
    {
        Plan::factory()->create(['stripe_price_id' => 'price_monthly', 'price' => 100]);

        $this->subscribe('price_monthly');
        $this->subscribe('price_monthly');
        $this->subscribe('price_monthly');
        $this->subscribe('price_monthly', 'canceled', now()->subDays(5)->toDateTimeString());
        $this->subscribe('price_monthly', 'canceled', now()->subDays(90)->toDateTimeString());

        $churn = app(SubscriptionMetrics::class)->churn(30);

        $this->assertSame(1, $churn['canceled']);
        $this->assertSame(25.0, $churn['rate']);
    }

    public function test_churn_is_zero_without_activity(): void
    {
        $churn = app(SubscriptionMetrics::class)->churn();

        $this->assertSame(0, $churn['canceled']);
        $this->assertSame(0.0, $churn['rate']);
    }

    public function test_admin_widget_renders_the_metrics(): void
    {
        Plan::factory()->create(['stripe_price_id' => 'price_monthly', 'price' => 250]);
        $this->subscribe('price_monthly');

        Livewire::test(SubscriptionMetricsWidget::class)
            ->assertSee('MRR')
            ->assertSee('250.00')
            ->assertSee('Abonamente active')
            ->assertSee('Churn (30 zile)');

        $this->assertSame(1, Subscription::query()->where('type', 'host')->count());
    }
}
