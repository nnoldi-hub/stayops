<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public ?string $reason = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $body = 'Rezervarea '.$this->booking->reference.' a fost anulată.';

        if (filled($this->reason)) {
            $body .= ' Motiv: '.$this->reason;
        }

        return [
            'title' => 'Rezervare anulată',
            'body' => $body,
            'url' => route('bookings.show', $this->booking),
            'booking_id' => $this->booking->id,
        ];
    }
}
