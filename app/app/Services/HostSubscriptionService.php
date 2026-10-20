<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use InvalidArgumentException;
use Laravel\Cashier\Cashier;
use Stripe\StripeClient;

class HostSubscriptionService
{
    /**
     * Resolved lazily so that the service can be validated/unit tested without
     * Stripe credentials configured.
     */
    private function stripe(): StripeClient
    {
        return Cashier::stripe();
    }

    /**
     * Start a Stripe hosted checkout for the host platform subscription.
     * The returned value is the hosted checkout URL the user is sent to.
     */
    public function startCheckout(User $user, Plan $plan, string $successUrl, string $cancelUrl): string
    {
        if (! $plan->is_active) {
            throw new InvalidArgumentException('Planul selectat nu mai este disponibil.');
        }

        if (blank($plan->stripe_price_id)) {
            throw new InvalidArgumentException('Planul selectat nu are configurat un pret in Stripe.');
        }

        $session = $this->stripe()->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $this->resolveCustomerId($user),
            'line_items' => [
                [
                    'price' => $plan->stripe_price_id,
                    'quantity' => 1,
                ],
            ],
            'subscription_data' => [
                'metadata' => [
                    'type' => 'host',
                    'user_id' => (string) $user->id,
                    'plan' => $plan->slug,
                ],
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        if (! is_string($session->url) || $session->url === '') {
            throw new InvalidArgumentException('Stripe nu a putut genera linkul de plata.');
        }

        return $session->url;
    }

    /**
     * Cancel the host subscription at the end of the billing period.
     */
    public function cancel(User $user): bool
    {
        $subscription = $user->subscription('host');

        if (! $subscription || $subscription->canceled()) {
            return false;
        }

        $subscription->cancel();

        return true;
    }

    /**
     * Reuse the stored Stripe customer, or create (and store) a new one.
     */
    public function resolveCustomerId(User $user): string
    {
        if (filled($user->stripe_id)) {
            return $user->stripe_id;
        }

        $customer = $this->stripe()->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => (string) $user->id,
            ],
        ]);

        $user->forceFill(['stripe_id' => $customer->id])->save();

        return $customer->id;
    }
}
