<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeSubscriptionWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function billable(): User
    {
        /** @var User $user */
        $user = User::factory()->create(['stripe_id' => 'cus_billable']);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(string $event, array $overrides = []): array
    {
        return [
            'type' => $event,
            'data' => [
                'object' => array_merge([
                    'id' => 'sub_webhook_1',
                    'customer' => 'cus_billable',
                    'status' => 'active',
                    'quantity' => 1,
                    'cancel_at_period_end' => false,
                    'trial_end' => null,
                    'metadata' => ['type' => 'host'],
                    'items' => [
                        'data' => [
                            [
                                'price' => ['id' => 'price_web'],
                                'current_period_end' => now()->addMonth()->timestamp,
                            ],
                        ],
                    ],
                ], $overrides),
            ],
        ];
    }

    public function test_subscription_created_event_stores_the_subscription(): void
    {
        $this->billable();

        $this->postJson(route('stripe.webhook'), $this->payload('customer.subscription.created'))
            ->assertOk()
            ->assertJson(['received' => true]);

        $this->assertDatabaseHas('subscriptions', [
            'stripe_id' => 'sub_webhook_1',
            'type' => 'host',
            'stripe_status' => 'active',
            'stripe_price' => 'price_web',
            'quantity' => 1,
            'ends_at' => null,
            'trial_ends_at' => null,
        ]);
    }

    public function test_subscription_created_event_stores_trial_end(): void
    {
        $this->billable();

        $trialEnd = now()->addDays(14)->startOfSecond();

        $this->postJson(route('stripe.webhook'), $this->payload('customer.subscription.created', [
            'status' => 'trialing',
            'trial_end' => $trialEnd->timestamp,
        ]))->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'stripe_id' => 'sub_webhook_1',
            'stripe_status' => 'trialing',
            'trial_ends_at' => $trialEnd->toDateTimeString(),
        ]);
    }

    public function test_subscription_updated_event_records_the_cancellation_grace_period(): void
    {
        $this->billable();

        $periodEnd = now()->addDays(20)->startOfSecond();

        $this->postJson(route('stripe.webhook'), $this->payload('customer.subscription.updated', [
            'cancel_at_period_end' => true,
            'items' => [
                'data' => [
                    [
                        'price' => ['id' => 'price_web'],
                        'current_period_end' => $periodEnd->timestamp,
                    ],
                ],
            ],
        ]))->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'stripe_id' => 'sub_webhook_1',
            'stripe_status' => 'active',
            'ends_at' => $periodEnd->toDateTimeString(),
        ]);
    }

    public function test_subscription_updated_event_is_idempotent(): void
    {
        $this->billable();

        $this->postJson(route('stripe.webhook'), $this->payload('customer.subscription.created'))->assertOk();
        $this->postJson(route('stripe.webhook'), $this->payload('customer.subscription.updated', [
            'status' => 'past_due',
        ]))->assertOk();

        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertDatabaseHas('subscriptions', [
            'stripe_id' => 'sub_webhook_1',
            'stripe_status' => 'past_due',
        ]);
    }

    public function test_subscription_deleted_event_cancels_the_subscription(): void
    {
        $this->billable();

        $this->postJson(route('stripe.webhook'), $this->payload('customer.subscription.created'))->assertOk();
        $this->postJson(route('stripe.webhook'), $this->payload('customer.subscription.deleted'))->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'stripe_id' => 'sub_webhook_1',
            'stripe_status' => 'canceled',
        ]);

        $this->assertNotNull(
            User::query()->where('stripe_id', 'cus_billable')->first()?->subscriptions()->first()?->ends_at
        );
    }

    public function test_unknown_customer_is_ignored(): void
    {
        $this->postJson(route('stripe.webhook'), $this->payload('customer.subscription.created', [
            'customer' => 'cus_missing',
        ]))->assertOk()
            ->assertJson(['received' => true]);

        $this->assertDatabaseCount('subscriptions', 0);
    }
}
