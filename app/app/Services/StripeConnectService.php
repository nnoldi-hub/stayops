<?php

namespace App\Services;

use App\Models\Host;
use Stripe\StripeClient;

class StripeConnectService
{
    public function __construct(
        private readonly StripeClient $stripe
    ) {}

    /**
     * Create a Stripe Express account for the host (idempotent: skips if already exists).
     */
    public function createAccount(Host $host): Host
    {
        if ($host->stripe_account_id) {
            return $host;
        }

        $account = $this->stripe->accounts->create([
            'type'    => 'express',
            'country' => $host->billing_country ?: 'RO',
            'email'   => $host->user->email,
            'metadata' => [
                'host_id' => $host->id,
            ],
        ]);

        $host->update([
            'stripe_account_id'     => $account->id,
            'stripe_account_status' => 'pending',
        ]);

        return $host->fresh();
    }

    /**
     * Generate a Stripe hosted onboarding URL.
     */
    public function createOnboardingLink(Host $host, string $returnUrl, string $refreshUrl): string
    {
        $link = $this->stripe->accountLinks->create([
            'account'     => $host->stripe_account_id,
            'refresh_url' => $refreshUrl,
            'return_url'  => $returnUrl,
            'type'        => 'account_onboarding',
        ]);

        return $link->url;
    }

    /**
     * Fetch the account from Stripe and sync status to the Host model.
     */
    public function refreshAccountStatus(Host $host): Host
    {
        if (! $host->stripe_account_id) {
            return $host;
        }

        $account = $this->stripe->accounts->retrieve($host->stripe_account_id);

        $status = match (true) {
            $account->charges_enabled  => 'active',
            $account->details_submitted => 'restricted',
            default                    => 'pending',
        };

        $host->update([
            'stripe_account_status' => $status,
            'onboarded_at'          => $account->charges_enabled ? ($host->onboarded_at ?? now()) : null,
        ]);

        return $host->fresh();
    }
}
