<?php

namespace App\Services;

use App\Models\Property;
use App\Models\User;
use Laravel\Cashier\Cashier;
use Stripe\StripeClient;

class BookingPaymentService
{
    /**
     * The Stripe client is resolved lazily so that environments without
     * Stripe credentials can still create bookings (the intent is optional).
     */
    private function stripe(): StripeClient
    {
        return Cashier::stripe();
    }

    /**
     * Create the guest payment intent for a booking.
     *
     * When the property belongs to a connected host, the platform fee is
     * charged as a Stripe application fee and the remainder is transferred
     * to the host's connected account.
     */
    public function createPaymentIntent(
        User $guest,
        Property $property,
        float $totalPrice,
        string $currency,
        string $reference,
    ): string {
        $amount = (int) round($totalPrice * 100);

        $params = [
            'amount' => $amount,
            'currency' => strtolower($currency),
            'customer' => $guest->createOrGetStripeCustomer()->id,
            'metadata' => [
                'property_id' => (string) $property->id,
                'booking_reference' => $reference,
            ],
        ];

        $host = $property->host;
        $fee = $this->applicationFeeAmount($property, $amount);

        if ($fee > 0 && $host && ! empty($host->stripe_account_id)) {
            $params['application_fee_amount'] = $fee;
            $params['transfer_data'] = [
                'destination' => (string) $host->stripe_account_id,
            ];
        }

        return $this->stripe()->paymentIntents->create($params)->id;
    }

    /**
     * Platform fee (in minor units) for the given gross amount.
     */
    public function applicationFeeAmount(Property $property, int $amount): int
    {
        $percentage = $property->host?->platformFeePct() ?? 0.0;

        if ($percentage <= 0) {
            return 0;
        }

        return (int) round($amount * $percentage / 100);
    }
}
