<?php

use App\Models\Booking;
use App\Models\Review;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] #[Title('Rezervare confirmată')] class extends Component {
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        // Doar guest-ul care a făcut rezervarea sau host-ul proprietății pot vedea pagina.
        $user = auth()->user();
        abort_unless(
            $user && (
                $booking->guest_id === $user->id
                || $booking->property?->host?->user_id === $user->id
            ),
            403
        );

        $booking->load('property.host.user', 'guest');
        $this->booking = $booking;
    }

    /**
     * Persoana de contactat (gazda, dacă ești oaspete; oaspetele, dacă ești gazdă).
     */
    #[Computed]
    public function contactTarget(): ?\App\Models\User
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if ($this->booking->guest_id === $user->id) {
            return $this->booking->property?->host?->user;
        }

        if ($this->booking->property?->host?->user_id === $user->id) {
            return $this->booking->guest;
        }

        return null;
    }

    #[Computed]
    public function canReview(): bool
    {
        $user = auth()->user();
        if (! $user || $this->booking->guest_id !== $user->id) {
            return false;
        }
        if ($this->booking->status !== 'checked_out') {
            return false;
        }
        return ! Review::query()
            ->where('booking_id', $this->booking->id)
            ->where('author_id', $user->id)
            ->exists();
    }
}; ?>

<div>
    <div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl mx-auto">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Mulțumim, rezervarea ta a fost înregistrată</h1>
            <span class="inline-block text-xs uppercase tracking-wide bg-yellow-100 text-yellow-800 rounded px-2 py-1" data-testid="booking-status">
                {{ $booking->status }}
            </span>
        </div>
        <p class="text-sm text-gray-600 mt-2">Cod rezervare: <span class="font-mono font-semibold" data-testid="booking-reference">{{ $booking->reference }}</span></p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6 text-sm">
            <div>
                <div class="text-gray-500">Proprietate</div>
                <div class="font-medium text-gray-900">{{ $booking->property->title }}</div>
            </div>
            <div>
                <div class="text-gray-500">Oraș</div>
                <div class="font-medium text-gray-900">{{ $booking->property->city }}</div>
            </div>
            <div>
                <div class="text-gray-500">Check-in</div>
                <div class="font-medium text-gray-900">{{ $booking->check_in->format('d M Y') }}</div>
            </div>
            <div>
                <div class="text-gray-500">Check-out</div>
                <div class="font-medium text-gray-900">{{ $booking->check_out->format('d M Y') }}</div>
            </div>
            <div>
                <div class="text-gray-500">Nopți</div>
                <div class="font-medium text-gray-900">{{ $booking->nights }}</div>
            </div>
            <div>
                <div class="text-gray-500">Oaspeți</div>
                <div class="font-medium text-gray-900">{{ $booking->guests_count }}</div>
            </div>
        </div>

        <div class="mt-6 border-t pt-4 text-sm space-y-1">
            <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format((float) $booking->subtotal, 2) }} {{ $booking->currency }}</span></div>
            <div class="flex justify-between"><span>Taxă curățenie</span><span>{{ number_format((float) $booking->cleaning_fee, 2) }} {{ $booking->currency }}</span></div>
            <div class="flex justify-between font-semibold border-t pt-2 mt-2"><span>Total</span><span data-testid="booking-total">{{ number_format((float) $booking->total_price, 2) }} {{ $booking->currency }}</span></div>
        </div>

        <div class="mt-6 text-sm text-gray-600">
            Rezervarea este în starea <strong>pending</strong>. Vei primi confirmarea după validarea de către gazdă și a plății.
        </div>

        <div class="mt-6 flex gap-3">
            <a href="{{ route('properties.index') }}" class="text-sm text-gray-700 underline">Înapoi la căutare</a>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-700 underline">Spre dashboard</a>
            @if ($this->contactTarget)
                <a
                    href="{{ route('messages.start', $this->contactTarget) }}?booking={{ $booking->reference }}"
                    class="text-sm text-gray-700 underline"
                    data-testid="contact-link"
                >
                    Contactează {{ $booking->guest_id === auth()->id() ? 'gazda' : 'oaspetele' }}
                </a>
            @endif
            @if ($this->canReview)
                <a href="{{ route('bookings.review', $booking) }}"
                   class="ml-auto bg-indigo-600 text-white text-sm font-semibold px-3 py-1.5 rounded hover:bg-indigo-700"
                   data-testid="leave-review-link">Lasă un review</a>
            @endif
        </div>
    </div>
</div>
