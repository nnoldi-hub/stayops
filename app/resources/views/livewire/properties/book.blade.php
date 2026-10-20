<?php

use App\Models\Booking;
use App\Models\Property;
use App\Services\BookingService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] #[Title('Rezervă proprietate')] class extends Component {
    public Property $property;

    public string $checkIn = '';
    public string $checkOut = '';
    public int $guestsCount = 1;
    public string $specialRequests = '';

    public ?string $errorMessage = null;

    public function mount(Property $property): void
    {
        abort_unless($property->status === 'published' && $property->host?->kyc_status === 'approved', 404);
        $this->property = $property;
        $this->checkIn = now()->addDay()->toDateString();
        $this->checkOut = now()->addDays(3)->toDateString();
    }

    #[Computed]
    public function nights(): int
    {
        if (! $this->checkIn || ! $this->checkOut) {
            return 0;
        }
        try {
            $in = Carbon::parse($this->checkIn);
            $out = Carbon::parse($this->checkOut);
            $diff = (int) $in->diffInDays($out, false);

            return $diff > 0 ? $diff : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    #[Computed]
    public function subtotal(): float
    {
        return round((float) $this->property->base_price * $this->nights(), 2);
    }

    #[Computed]
    public function total(): float
    {
        return round($this->subtotal() + (float) $this->property->cleaning_fee, 2);
    }

    public function save(BookingService $service)
    {
        $this->errorMessage = null;

        $data = $this->validate([
            'checkIn' => ['required', 'date', 'after_or_equal:today'],
            'checkOut' => ['required', 'date', 'after:checkIn'],
            'guestsCount' => ['required', 'integer', 'min:1', 'max:' . max(1, (int) $this->property->max_guests)],
            'specialRequests' => ['nullable', 'string', 'max:1000'],
        ]);

        $nights = $this->nights();
        if ($nights < 1) {
            $this->addError('checkOut', 'Intervalul trebuie să fie de cel puțin o noapte.');

            return null;
        }

        $rate = (float) $this->property->base_price;
        $subtotal = round($rate * $nights, 2);
        $cleaningFee = (float) $this->property->cleaning_fee;
        $total = round($subtotal + $cleaningFee, 2);

        $user = auth()->user();

        try {
            $booking = $service->create($this->property->id, [
                'guest_id' => $user?->id,
                'guest_name' => $user?->name ?? '',
                'guest_email' => $user?->email ?? '',
                'check_in' => $data['checkIn'],
                'check_out' => $data['checkOut'],
                'nights' => $nights,
                'guests_count' => $data['guestsCount'],
                'adults' => $data['guestsCount'],
                'children' => 0,
                'nightly_rate' => $rate,
                'subtotal' => $subtotal,
                'cleaning_fee' => $cleaningFee,
                'platform_fee' => 0,
                'host_payout' => $subtotal,
                'total_price' => $total,
                'currency' => $this->property->currency ?? 'RON',
                'status' => 'pending',
                'payment_status' => 'pending',
                'source' => 'direct',
                'special_requests' => $data['specialRequests'] ?: null,
            ]);
        } catch (\RuntimeException $e) {
            $this->errorMessage = 'Intervalul ales se suprapune cu o altă rezervare activă. Te rugăm să alegi alte date.';

            return null;
        }
        return redirect()->route('bookings.show', $booking);
    }
}; ?>

<div>
    <div class="mb-6">
        <a href="{{ route('properties.show', $property) }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Înapoi la proprietate</a>
        <h1 class="text-2xl font-semibold text-gray-900 mt-2">Rezervă: {{ $property->title }}</h1>
        <p class="text-sm text-gray-600 mt-1">{{ $property->city }} · {{ $property->property_type }}</p>
    </div>

    @if ($errorMessage)
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-md p-3 mb-4 text-sm" data-testid="overbooking-error">
            {{ $errorMessage }}
        </div>
    @endif

    <form wire:submit="save" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="block">
                    <span class="text-sm text-gray-700">Check-in</span>
                    <input type="date" wire:model.live="checkIn" class="mt-1 block w-full border-gray-300 rounded-md text-sm" />
                    @error('checkIn') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm text-gray-700">Check-out</span>
                    <input type="date" wire:model.live="checkOut" class="mt-1 block w-full border-gray-300 rounded-md text-sm" />
                    @error('checkOut') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>
            <label class="block">
                <span class="text-sm text-gray-700">Oaspeți (max. {{ $property->max_guests }})</span>
                <input type="number" min="1" max="{{ $property->max_guests }}" wire:model="guestsCount" class="mt-1 block w-full border-gray-300 rounded-md text-sm" />
                @error('guestsCount') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span class="text-sm text-gray-700">Cerințe speciale (opțional)</span>
                <textarea wire:model="specialRequests" rows="3" class="mt-1 block w-full border-gray-300 rounded-md text-sm"></textarea>
                @error('specialRequests') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
        </div>

        <aside class="bg-white rounded-lg shadow-sm p-6 h-fit">
            <div class="text-sm text-gray-600">{{ number_format((float) $property->base_price, 0) }} {{ $property->currency }} / noapte</div>
            <div class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span>{{ $this->nights }} nopți × {{ number_format((float) $property->base_price, 0) }} {{ $property->currency }}</span>
                    <span>{{ number_format($this->subtotal, 2) }} {{ $property->currency }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Taxă de curățenie</span>
                    <span>{{ number_format((float) $property->cleaning_fee, 2) }} {{ $property->currency }}</span>
                </div>
                <div class="flex justify-between font-semibold border-t pt-2 mt-2">
                    <span>Total</span>
                    <span>{{ number_format($this->total, 2) }} {{ $property->currency }}</span>
                </div>
            </div>
            <button type="submit" class="mt-6 w-full bg-gray-900 text-white text-sm font-medium rounded-md py-2 hover:bg-gray-800" data-testid="confirm-booking">
                Confirmă rezervarea
            </button>
            <p class="text-xs text-gray-500 mt-2">Vei putea finaliza plata în pasul următor.</p>
        </aside>
    </form>
</div>
