<?php

use App\Models\Amenity;
use App\Models\Property;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.public')] #[Title('Proprietăți disponibile')] class extends Component {
    use WithPagination;

    public string $search = '';
    public ?string $city = null;
    public ?string $propertyType = null;
    public ?int $minGuests = null;
    public ?float $lat = null;
    public ?float $lng = null;
    public ?float $radiusKm = null;
    /** @var array<int,int> */
    public array $amenities = [];

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'city', 'propertyType', 'minGuests', 'lat', 'lng', 'radiusKm', 'amenities'], true)) {
            $this->resetPage();
        }
    }

    public function with(): array
    {
        $query = Property::query()
            ->where('status', 'published')
            ->when($this->search, fn ($q, $s) => $q->where(fn ($qq) => $qq
                ->where('title', 'ilike', "%{$s}%")
                ->orWhere('description', 'ilike', "%{$s}%")
            ))
            ->when($this->city, fn ($q, $c) => $q->where('city', 'ilike', "%{$c}%"))
            ->when($this->propertyType, fn ($q, $t) => $q->where('property_type', $t))
            ->when($this->minGuests, fn ($q, $g) => $q->where('max_guests', '>=', $g))
            ->when(
                $this->lat !== null && $this->lng !== null && $this->radiusKm !== null && $this->radiusKm > 0,
                fn ($q) => $q->whereRaw(
                    'ST_DWithin(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                    [(float) $this->lng, (float) $this->lat, (float) $this->radiusKm * 1000]
                )
            )
            ->when(! empty($this->amenities), function ($q) {
                $ids = array_map('intval', $this->amenities);
                foreach ($ids as $id) {
                    $q->whereHas('amenities', fn ($qq) => $qq->where('amenities.id', $id));
                }
            })
            ->orderByDesc('rating_avg')
            ->orderByDesc('id');

        return [
            'properties' => $query->paginate(12),
            'allAmenities' => Amenity::query()->orderBy('sort_order')->orderBy('name_ro')->get(),
        ];
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Proprietăți disponibile</h1>
        <p class="text-sm text-gray-600 mt-1">Cazări verificate, gata de rezervare.</p>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-3">
        <input
            type="text"
            wire:model.live.debounce.400ms="search"
            placeholder="Caută titlu / descriere…"
            class="border-gray-300 rounded-md text-sm"
        />
        <input
            type="text"
            wire:model.live.debounce.400ms="city"
            placeholder="Oraș"
            class="border-gray-300 rounded-md text-sm"
        />
        <select wire:model.live="propertyType" class="border-gray-300 rounded-md text-sm">
            <option value="">Tip — toate</option>
            <option value="apartment">Apartament</option>
            <option value="house">Casă</option>
            <option value="villa">Vilă</option>
            <option value="studio">Studio</option>
        </select>
        <input
            type="number"
            min="1"
            wire:model.live.debounce.400ms="minGuests"
            placeholder="Oaspeți minim"
            class="border-gray-300 rounded-md text-sm"
        />
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-3">
        <input
            type="number"
            step="0.000001"
            wire:model.live.debounce.500ms="lat"
            placeholder="Latitudine (ex. 44.4268)"
            class="border-gray-300 rounded-md text-sm"
        />
        <input
            type="number"
            step="0.000001"
            wire:model.live.debounce.500ms="lng"
            placeholder="Longitudine (ex. 26.1025)"
            class="border-gray-300 rounded-md text-sm"
        />
        <input
            type="number"
            step="0.1"
            min="0"
            wire:model.live.debounce.500ms="radiusKm"
            placeholder="Rază (km)"
            class="border-gray-300 rounded-md text-sm"
        />
        <div class="text-xs text-gray-500 self-center">Caută într-o rază geografică (PostGIS).</div>
    </div>

    @if ($allAmenities->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="text-xs uppercase text-gray-500 mb-2">Dotări</div>
            <div class="flex flex-wrap gap-3">
                @foreach ($allAmenities as $a)
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model.live="amenities" value="{{ $a->id }}" class="rounded border-gray-300" />
                        {{ $a->name_ro ?? $a->name_en ?? $a->slug }}
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    @if ($properties->isEmpty())
        <div class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-500" data-testid="empty-state">
            Nicio proprietate găsită pentru filtrele curente.
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" data-testid="properties-grid">
            @foreach ($properties as $property)
                @php($cover = $property->getFirstMediaUrl('photos', 'thumb'))
                <a href="{{ route('properties.show', $property) }}" class="block bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition">
                    <div class="aspect-[4/3] bg-gray-200">
                        @if ($cover)
                            <img src="{{ $cover }}" alt="{{ $property->title }}" class="w-full h-full object-cover" />
                        @endif
                    </div>
                    <div class="p-4">
                        <div class="text-xs uppercase text-gray-500">{{ $property->city }} · {{ $property->property_type }}</div>
                        <h2 class="font-semibold text-gray-900 mt-1">{{ $property->title }}</h2>
                        <div class="mt-2 text-sm text-gray-700">
                            <span class="font-medium">{{ number_format((float) $property->base_price, 0) }} {{ $property->currency }}</span>
                            <span class="text-gray-500"> / noapte · {{ $property->max_guests }} oaspeți</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $properties->links() }}
        </div>
    @endif
</div>
