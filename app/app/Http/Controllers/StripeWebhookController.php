<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, BookingService $bookingService): JsonResponse
    {
        $event = $request->input('type');
        $object = $request->input('data.object', []);

        if ($event === 'payment_intent.succeeded') {
            $paymentIntentId = $object['id'] ?? null;

            if ($paymentIntentId) {
                $booking = Booking::query()
                    ->where('stripe_payment_intent_id', $paymentIntentId)
                    ->first();

                if ($booking) {
                    $booking->payment_status = 'paid';
                    $booking->save();

                    if ($booking->status === 'pending' && $booking->canTransitionTo('confirmed')) {
                        try {
                            $bookingService->confirm($booking);
                        } catch (\Throwable $e) {
                            Log::error('Stripe webhook confirm failed', [
                                'booking_id' => $booking->id,
                                'payment_intent_id' => $paymentIntentId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }

        if (is_string($event) && str_starts_with($event, 'customer.subscription.')) {
            $this->syncSubscription($event, is_array($object) ? $object : []);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Keep the local Cashier subscription in sync with Stripe's source of truth.
     *
     * @param  array<string, mixed>  $object
     */
    private function syncSubscription(string $event, array $object): void
    {
        $stripeSubscriptionId = $object['id'] ?? null;

        if (! is_string($stripeSubscriptionId) || $stripeSubscriptionId === '') {
            return;
        }

        $customerId = $object['customer'] ?? null;
        $billable = is_string($customerId) ? Cashier::findBillable($customerId) : null;

        if (! $billable instanceof User) {
            Log::warning('Stripe webhook: subscription belongs to an unknown customer', [
                'event' => $event,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'customer' => is_string($customerId) ? $customerId : null,
            ]);

            return;
        }

        $quantity = $object['quantity'] ?? null;
        $status = (string) ($object['status'] ?? 'active');
        $type = (string) ($object['metadata']['type'] ?? 'host');
        $price = $object['items']['data'][0]['price']['id'] ?? null;
        $periodEnd = $object['items']['data'][0]['current_period_end'] ?? $object['current_period_end'] ?? null;
        $trialEndsAt = is_numeric($object['trial_end'] ?? null)
            ? Carbon::createFromTimestamp((int) $object['trial_end'])
            : null;

        if ($event === 'customer.subscription.deleted') {
            $billable->subscriptions()
                ->where('stripe_id', $stripeSubscriptionId)
                ->update(['stripe_status' => 'canceled', 'ends_at' => now()]);

            return;
        }

        $endsAt = null;

        if (! empty($object['cancel_at_period_end']) && is_numeric($periodEnd)) {
            $endsAt = Carbon::createFromTimestamp((int) $periodEnd);
        } elseif ($status === 'canceled') {
            $endsAt = now();
        }

        $billable->subscriptions()->updateOrCreate(
            ['stripe_id' => $stripeSubscriptionId],
            [
                'type' => $type,
                'stripe_status' => $status,
                'stripe_price' => is_string($price) ? $price : null,
                'quantity' => is_numeric($quantity) ? (int) $quantity : null,
                'trial_ends_at' => $trialEndsAt,
                'ends_at' => $endsAt,
            ]
        );
    }
}
