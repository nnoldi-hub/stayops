<?php

namespace Database\Factories;

use App\Models\Host;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Host>
 */
class HostFactory extends Factory
{
    protected $model = Host::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_name' => fake()->company(),
            'cui' => 'RO'.fake()->numerify('########'),
            'registration_number' => 'J'.fake()->numerify('##/####/####'),
            'billing_address' => fake()->streetAddress(),
            'billing_city' => fake()->city(),
            'billing_country' => 'RO',
            'vat_number' => null,
            'kyc_status' => 'approved',
            'kyc_documents' => [],
            'stripe_account_id' => null,
            'stripe_account_status' => 'pending',
            'onboarded_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Host $host): void {
            if ($host->user_id === null) {
                return;
            }

            $plan = Plan::query()->firstOrCreate(
                ['slug' => 'standard'],
                [
                    'name_ro' => 'Standard',
                    'name_en' => 'Standard',
                    'description_ro' => 'Planul standard pentru gazde.',
                    'description_en' => 'Standard plan for hosts.',
                    'price' => 99,
                    'currency' => 'RON',
                    'interval' => 'month',
                    'features' => [],
                    'max_properties' => null,
                    'platform_fee_pct' => 10,
                    'stripe_price_id' => 'price_host_standard',
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );

            $host->user->subscriptions()->firstOrCreate(
                ['stripe_id' => 'sub_host_'.$host->user_id],
                [
                    'type' => 'host',
                    'stripe_status' => 'active',
                    'stripe_price' => $plan->stripe_price_id,
                    'quantity' => 1,
                    'ends_at' => null,
                ]
            );
        });
    }

    public function pending(): static
    {
        return $this->state(['kyc_status' => 'pending', 'onboarded_at' => null]);
    }

    /**
     * Opt out of the default active platform subscription (for gating tests).
     */
    public function withoutSubscription(): static
    {
        return $this->afterCreating(function (Host $host): void {
            $host->user?->subscriptions()->where('type', 'host')->delete();
        });
    }

    /**
     * Mark the default subscription as cancelled at period end (grace period).
     */
    public function unsubscribed(): static
    {
        return $this->afterCreating(function (Host $host): void {
            $host->user?->subscriptions()
                ->where('type', 'host')
                ->update(['stripe_status' => 'canceled', 'ends_at' => now()->addDays(14)]);
        });
    }
}
