<?php

use App\Models\Booking;
use App\Models\Host;
use App\Services\BookingService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.public')] #[Title('Host — Rezervări')] class extends Component {
    use WithPagination;

    public string $cancelReason = '';
    public ?int $cancelTargetId = null;

    #[Computed]
    public function host(): ?Host
    {
        return Host::query()->where('user_id', auth()->id())->first();
    }

    private function ownBooking(int $bookingId): Booking
    {
        $host = $this->host;
        abort_unless($host !== null, 403);

        return Booking::query()
            ->whereHas('property', fn ($q) => $q->where('host_id', $host->id))
            ->where('id', $bookingId)
            ->firstOrFail();
    }

    public function confirm(int $bookingId): void
    {
        $booking = $this->ownBooking($bookingId);
        app(BookingService::class)->confirm($booking);
        $this->dispatch('booking-updated');
    }

    public function openCancel(int $bookingId): void
    {
        $this->cancelTargetId = $bookingId;
        $this->cancelReason = '';
    }

    public function cancelBooking(): void
    {
        $this->validate(['cancelReason' => 'nullable|string|max:500']);
        $booking = $this->ownBooking($this->cancelTargetId);
        app(BookingService::class)->cancel($booking, $this->cancelReason ?: null);
        $this->cancelTargetId = null;
        $this->cancelReason = '';
        $this->dispatch('booking-updated');
    }

    public function checkIn(int $bookingId): void
    {
        $booking = $this->ownBooking($bookingId);
        app(BookingService::class)->checkIn($booking);
        $this->dispatch('booking-updated');
    }

    public function checkOut(int $bookingId): void
    {
        $booking = $this->ownBooking($bookingId);
        app(BookingService::class)->checkOut($booking);
        $this->dispatch('booking-updated');
    }

    public function with(): array
    {
        $host = $this->host;
        $bookings = $host
            ? Booking::query()
                ->whereHas('property', fn ($q) => $q->where('host_id', $host->id))
                ->with('property')
                ->orderByDesc('id')
                ->paginate(15)
            : Booking::query()->whereRaw('1=0')->paginate(15);

        return ['bookings' => $bookings];
    }
}; ?>

<div>
    <div class="max-w-6xl mx-auto bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Rezervările mele</h1>
            <a href="{{ route('host.dashboard') }}" class="text-sm text-indigo-600 hover:underline">← Dashboard</a>
        </div>

        @if ($bookings->isEmpty())
            <p class="text-sm text-gray-500" data-testid="empty-bookings">Nu există rezervări pentru proprietățile tale.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Referință</th>
                        <th class="py-2">Proprietate</th>
                        <th class="py-2">Guest</th>
                        <th class="py-2">Check-in</th>
                        <th class="py-2">Check-out</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $b)
                        <tr class="border-b" data-testid="booking-row-{{ $b->id }}">
                            <td class="py-2 font-mono text-xs">{{ $b->reference }}</td>
                            <td class="py-2">{{ $b->property?->title }}</td>
                            <td class="py-2">{{ $b->guest_name }}</td>
                            <td class="py-2">{{ $b->check_in?->format('d.m.Y') }}</td>
                            <td class="py-2">{{ $b->check_out?->format('d.m.Y') }}</td>
                            <td class="py-2">
                                <span class="inline-block text-xs uppercase tracking-wide rounded px-2 py-1
                                    @if($b->status === 'confirmed') bg-green-100 text-green-700
                                    @elseif($b->status === 'pending') bg-yellow-100 text-yellow-700
                                    @elseif($b->status === 'checked_in') bg-blue-100 text-blue-700
                                    @elseif($b->status === 'checked_out') bg-gray-100 text-gray-600
                                    @elseif($b->status === 'cancelled') bg-red-100 text-red-600
                                    @else bg-gray-100 text-gray-500
                                    @endif"
                                    data-testid="booking-status-{{ $b->id }}">{{ $b->status }}</span>
                            </td>
                            <td class="py-2 flex gap-2 flex-wrap">
                                @if($b->status === 'pending')
                                    <button wire:click="confirm({{ $b->id }})"
                                            data-testid="confirm-booking-{{ $b->id }}"
                                            class="text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700">
                                        Confirmă
                                    </button>
                                @endif
                                @if(in_array($b->status, ['pending', 'confirmed']))
                                    <button wire:click="openCancel({{ $b->id }})"
                                            data-testid="cancel-booking-{{ $b->id }}"
                                            class="text-xs bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700">
                                        Anulează
                                    </button>
                                @endif
                                @if($b->status === 'confirmed')
                                    <button wire:click="checkIn({{ $b->id }})"
                                            data-testid="checkin-booking-{{ $b->id }}"
                                            class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700">
                                        Check-in
                                    </button>
                                @endif
                                @if($b->status === 'checked_in')
                                    <button wire:click="checkOut({{ $b->id }})"
                                            data-testid="checkout-booking-{{ $b->id }}"
                                            class="text-xs bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700">
                                        Check-out
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">{{ $bookings->links() }}</div>
        @endif

        {{-- Cancel modal --}}
        @if($cancelTargetId !== null)
            <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" data-testid="cancel-modal">
                <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
                    <h2 class="text-lg font-semibold mb-3">Anulare rezervare</h2>
                    <textarea wire:model="cancelReason"
                              rows="3"
                              placeholder="Motiv anulare (opțional)"
                              data-testid="cancel-reason-input"
                              class="w-full border rounded p-2 text-sm mb-4 focus:outline-none focus:ring-2 focus:ring-red-400"></textarea>
                    <div class="flex gap-3 justify-end">
                        <button wire:click="$set('cancelTargetId', null)"
                                class="text-sm px-3 py-1.5 border rounded hover:bg-gray-50">
                            Renunță
                        </button>
                        <button wire:click="cancelBooking"
                                data-testid="confirm-cancel-button"
                                class="text-sm bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700">
                            Confirmă anulare
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
