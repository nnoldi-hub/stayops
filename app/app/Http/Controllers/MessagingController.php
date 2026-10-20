<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessagingController extends Controller
{
    /**
     * Pornește (sau redeschide) o conversație directă cu un utilizator.
     * Opțional: `?booking=BK-XXX` leagă conversația de rezervare.
     */
    public function startWith(Request $request, User $user): RedirectResponse
    {
        $current = $request->user();

        abort_if($current === null || $current->is($user), 403);
        abort_if($user->isSuspended(), 403);

        $conversation = Conversation::findOrCreateDirect($current, $user);

        $reference = (string) $request->query('booking', '');

        if (filled($reference) && $conversation->booking_id === null) {
            $booking = Booking::query()
                ->where('reference', $reference)
                ->first();

            if ($booking !== null) {
                $participants = [$booking->guest_id, $booking->property?->host?->user_id];

                if (in_array($current->id, $participants, true)
                    && in_array($user->id, $participants, true)
                ) {
                    $conversation->forceFill([
                        'booking_id' => $booking->id,
                        'subject' => $conversation->subject ?? 'Rezervare '.$booking->reference,
                    ])->save();
                }
            }
        }

        return redirect()->route('messages.show', $conversation);
    }
}
