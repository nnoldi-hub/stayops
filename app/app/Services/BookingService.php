<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Property;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
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
}
