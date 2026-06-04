<?php

use App\Models\Property;
use App\Services\IcalSyncService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use App\Models\IcalFeed;

new #[Layout('components.layouts.public')] #[Title('Sincronizare iCal')] class extends Component {

    public Property $property;
    public string $newUrl = '';
    public string $newSourceName = '';
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount(Property $property): void
    {
        abort_unless($property->host?->user_id === auth()->id(), 403);
        $this->property = $property;
    }

    #[Computed]
    public function feeds()
    {
        return $this->property->icalFeeds()->where('direction', 'in')->orderBy('created_at')->get();
    }

    public function addFeed(): void
    {
        $this->validate([
            'newUrl'         => 'required|url|max:1024',
            'newSourceName'  => 'nullable|string|max:50',
        ]);

        IcalFeed::create([
            'property_id' => $this->property->id,
            'direction'   => 'in',
            'source_name' => $this->newSourceName ?: null,
            'url'         => $this->newUrl,
            'is_active'   => true,
        ]);

        $this->newUrl        = '';
        $this->newSourceName = '';
        $this->successMessage = 'Feed iCal adăugat cu succes.';
        $this->errorMessage   = null;
        unset($this->feeds);
    }

    public function deleteFeed(int $feedId): void
    {
        $this->property->icalFeeds()
            ->where('direction', 'in')
            ->findOrFail($feedId)
            ->delete();

        $this->successMessage = 'Feed șters.';
        $this->errorMessage   = null;
        unset($this->feeds);
    }

    public function syncFeed(int $feedId): void
    {
        $feed = $this->property->icalFeeds()
            ->where('direction', 'in')
            ->findOrFail($feedId);

        try {
            $count = app(IcalSyncService::class)->sync($feed);
            $this->successMessage = "Sincronizat: {$count} rezervări noi importate.";
            $this->errorMessage   = null;
        } catch (\Throwable $e) {
            $this->errorMessage   = 'Eroare sincronizare: ' . $e->getMessage();
            $this->successMessage = null;
        }

        unset($this->feeds);
    }

}; ?>

<div>
    <h2 class="text-xl font-semibold mb-4">Sincronizare iCal — {{ $property->name }}</h2>

    @if($successMessage)
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ $successMessage }}</div>
    @endif

    @if($errorMessage)
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">{{ $errorMessage }}</div>
    @endif

    {{-- Feed list --}}
    <div class="mb-6 space-y-3">
        @forelse($this->feeds as $feed)
            <div class="border rounded p-3 flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-sm">{{ $feed->source_name ?: 'Feed extern' }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ $feed->url }}</div>
                    <div class="text-xs text-gray-400 mt-1">
                        Ultimă sincronizare: {{ $feed->last_sync_at?->diffForHumans() ?? 'niciodată' }}
                        · Evenimente: {{ $feed->events_count }}
                    </div>
                    @if($feed->last_error)
                        <div class="text-xs text-red-600 mt-1">{{ $feed->last_error }}</div>
                    @endif
                </div>
                <div class="flex gap-2 shrink-0">
                    <button wire:click="syncFeed({{ $feed->id }})"
                            class="text-sm px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Sincronizează
                    </button>
                    <button wire:click="deleteFeed({{ $feed->id }})"
                            class="text-sm px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">
                        Șterge
                    </button>
                </div>
            </div>
        @empty
            <p class="text-gray-500 text-sm">Niciun feed iCal configurat.</p>
        @endforelse
    </div>

    {{-- Add feed form --}}
    <form wire:submit="addFeed" class="space-y-3 border-t pt-4">
        <h3 class="font-medium">Adaugă feed de import (iCal)</h3>

        <div>
            <label class="block text-sm mb-1">URL iCal *</label>
            <input wire:model="newUrl" type="url"
                   class="w-full border rounded px-3 py-2 text-sm"
                   placeholder="https://www.airbnb.com/calendar/ical/...">
            @error('newUrl') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Sursă (Airbnb, Booking.com etc.)</label>
            <input wire:model="newSourceName" type="text"
                   class="w-full border rounded px-3 py-2 text-sm"
                   placeholder="airbnb">
            @error('newSourceName') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
        </div>

        <button type="submit"
                class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm">
            Adaugă feed
        </button>
    </form>
</div>
