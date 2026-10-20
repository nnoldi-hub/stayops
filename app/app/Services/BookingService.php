<?php

namespace App\Services;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class BookingService
{
    public function __construct(
        private readonly BookingPaymentService $payments
    ) {}

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

            if ($property->host && ! $property->host->hasActiveSubscription()) {
                throw new RuntimeException(
                    "Rezervare refuzata: proprietatea #{$propertyId} nu apartine unei gazde cu abonament activ."
                );
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
                    "Overbooking refuzat: proprietatea #{$propertyId} are deja o rezervare activa �n intervalul {$checkIn} ? {$checkOut}."
                );
            }

            $attributes['property_id'] = $propertyId;
            $attributes['reference'] ??= 'BK-'.strtoupper(Str::random(8));
            $attributes['payment_status'] ??= 'pending';

            if (isset($attributes['guest_id']) && isset($attributes['total_price'])) {
                try {
                    $guest = User::find($attributes['guest_id']);
                    if ($guest) {
                        $attributes['stripe_payment_intent_id'] = $this->payments->createPaymentIntent(
                            $guest,
                            $property,
                            (float) $attributes['total_price'],
                            (string) ($attributes['currency'] ?? 'usd'),
                            (string) ($attributes['reference'] ?? ''),
                        );
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

        $this->notifyParties($booking, new BookingConfirmedNotification($booking));

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

        $this->notifyParties($booking, new BookingCancelledNotification($booking, $reason));

        return $booking->refresh();
    }

    /**
     * Notificare în-app către oaspete și gazdă (canal database).
     */
    private function notifyParties(Booking $booking, Notification $notification): void
    {
        $booking->loadMissing(['guest', 'property.host.user']);

        $candidates = [$booking->guest, $booking->property?->host?->user];
        $notified = [];

        foreach ($candidates as $recipient) {
            if ($recipient === null || isset($notified[$recipient->id])) {
                continue;
            }

            $notified[$recipient->id] = true;
            $recipient->notify($notification);
        }
    }

    public function checkIn(Booking $booking): Booking
    {
        if (! $booking->canTransitionTo('checked_in')) {
            throw new \DomainException("Booking #{$booking->id} cannot transition from {$booking->status} to checked_in.");
        }

        $booking->status = 'checked_in';
        $booking->save();

        return $booking->refresh();
    }

    public function checkOut(Booking $booking): Booking
    {
        if (! $booking->canTransitionTo('checked_out')) {
            throw new \DomainException("Booking #{$booking->id} cannot transition from {$booking->status} to checked_out.");
        }

        $booking->status = 'checked_out';
        $booking->save();

        return $booking->refresh();
    }
}
