<?php

namespace App\Services;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Property;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class BookingService
{
    /**
     * Creează o rezervare anti-overbooking, folosind row-level lock pe property
     * + verificare overlap în aceeași tranzacție.
     *
     * Statusurile considerate "active" (blochează intervalul): pending, confirmed, checked_in.
     * Statusurile finalizate/refuzate nu blochează (cancelled, refunded, no_show, checked_out, disputed).
     *
     * @param  array<string, mixed>  $attributes  Atribute Booking (fără reference — se generează dacă lipsește)
     *
     * @throws \RuntimeException dacă există overlap
     */
    public function create(int $propertyId, array $attributes): Booking
    {
        return DB::transaction(function () use ($propertyId, $attributes) {
            // Lock pe rândul proprietății — serializează creările concurente per property.
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

            // Overlap: existing.check_in < new.check_out  AND  existing.check_out > new.check_in
            $conflict = Booking::query()
                ->where('property_id', $propertyId)
                ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                ->where('check_in', '<', $checkOut)
                ->where('check_out', '>', $checkIn)
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw new RuntimeException(
                    "Overbooking refuzat: proprietatea #{$propertyId} are deja o rezervare activă în intervalul {$checkIn} → {$checkOut}."
                );
            }

            $attributes['property_id'] = $propertyId;
            $attributes['reference'] ??= 'BK-'.strtoupper(\Illuminate\Support\Str::random(8));

            return Booking::create($attributes);
        });
    }

    /**
     * Confirmă o rezervare (pending → confirmed). Trimite email guest-ului.
     *
     * @throws \DomainException dacă tranziția de stare nu este permisă
     */
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

    /**
     * Anulează o rezervare. Trimite email guest-ului cu motivul opțional.
     *
     * @throws \DomainException dacă tranziția de stare nu este permisă
     */
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
