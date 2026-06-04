<?php

namespace App\Services;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Property;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use RuntimeException;

class BookingService
{
    public function create(int $propertyId, array $attributes): Booking
    {
        return DB::transaction(function () use ($propertyId, $attributes) {
            $property = Property::query()
                ->whereKey($propertyId)
                ->lockForUpdate()
                ->first();

            if (! $property) {
                throw new ModelNotFoundException("Property #{$propertyId} not found.");
            }

            $checkIn = $attributes['check_in'] ?? null;
            $checkOut = $attributes['check_out'] ?? null;

            if (! $checkIn || ! $checkOut) {
                throw new \InvalidArgumentException('check_in and check_out are required.');
            }

            $conflict = Booking::query()
                ->where('property_id', $propertyId)
                ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                ->where('check_in', '<', $checkOut)
                ->where('check_out', '>', $checkIn)
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw new RuntimeException(
                    "Overbooking refuzat: proprietatea #{$propertyId} are deja o rezervare activa în intervalul {$checkIn} ? {$checkOut}."
                );
            }

            $attributes['property_id'] = $propertyId;
            $attributes['reference'] ??= 'BK-'.strtoupper(\Illuminate\Support\Str::random(8));
            $attributes['payment_status'] ??= 'pending';

            if (isset($attributes['guest_id']) && isset($attributes['total_price'])) {
                try {
                    $guest = \App\Models\User::find($attributes['guest_id']);
                    if ($guest) {
                        $stripeCustomer = $guest->createOrGetStripeCustomer();
                        $intent = Cashier::stripe()->paymentIntents->create([
                            'amount' => (int) ($attributes['total_price'] * 100),
                            'currency' => strtolower($attributes['currency'] ?? 'usd'),
                            'customer' => $stripeCustomer->id,
                            'metadata' => [
                                'property_id' => $propertyId,
                                'booking_reference' => $attributes['reference'],
                            ],
                        ]);
                        $attributes['stripe_payment_intent_id'] = $intent->id;
                    }
                } catch (\Exception $e) {
                    Log::error('Stripe payment intent failed', [
                        'error' => $e->getMessage(),
                        'guest_id' => $attributes['guest_id'],
                    ]);
                }
            }

            return Booking::create($attributes);
        });
    }

    public function confirm(Booking $booking): Booking
    {
        if (! $booking->canTransitionTo('confirmed')) {
            throw new \DomainException("Booking #{$booking->id} cannot transition from {$booking->status} to confirmed.");
        }

        $booking->status = 'confirmed';
        $booking->save();

        if ($booking->guest_email) {
            Mail::to($booking->guest_email)->send(new BookingConfirmedMail($booking->fresh(['property'])));
        }

        return $booking->refresh();
    }

    public function cancel(Booking $booking, ?string $reason = null): Booking
    {
        if (! $booking->canTransitionTo('cancelled')) {
            throw new \DomainException("Booking #{$booking->id} cannot transition from {$booking->status} to cancelled.");
        }

        $booking->status = 'cancelled';
        $booking->cancelled_at = now();
        $booking->cancellation_reason = $reason;
        $booking->save();

        if ($booking->guest_email) {
            Mail::to($booking->guest_email)->send(new BookingCancelledMail($booking->fresh(['property']), $reason));
        }

        return $booking->refresh();
    }
}
