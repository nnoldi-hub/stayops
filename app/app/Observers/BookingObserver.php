<?php

namespace App\Observers;

use App\Events\BookingCheckedOut;
use App\Models\Booking;
use App\Models\BookingStateTransition;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class BookingObserver
{
    /**
     * Validate any status change against the allowed transition map.
     */
    public function updating(Booking $booking): void
    {
        if (! $booking->isDirty('status')) {
            return;
        }

        $from = $booking->getOriginal('status');
        $to = $booking->status;

        if ($from === $to) {
            return;
        }

        $allowed = Booking::ALLOWED_TRANSITIONS[$from] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new InvalidArgumentException(
                "Invalid booking status transition: {$from} -> {$to}"
            );
        }
    }

    /**
     * Log the transition after the model is persisted.
     */
    public function updated(Booking $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }

        BookingStateTransition::create([
            'booking_id' => $booking->id,
            'from_state' => $booking->getOriginal('status'),
            'to_state' => $booking->status,
            'reason' => $booking->cancellation_reason,
            'actor_id' => Auth::id(),
            'transitioned_at' => now(),
        ]);

        if ($booking->status === 'checked_out') {
            BookingCheckedOut::dispatch($booking);
        }
    }
}
