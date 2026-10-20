<?php

use App\Models\Property;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component {
    public Property $property;

    public function mount(Property $property): void
    {
        $this->property = $property->load('amenities', 'host');

        if ($property->status !== 'published' || $property->host?->kyc_status !== 'approved') {
            abort(404);
        }
    }
}; ?>

<div>
    <a href="{{ route('properties.index') }}" class="text-sm text-gray-600 hover:underline">← Înapoi la listă</a>

    <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900">{{ $property->title }}</h1>
                <div class="text-sm text-gray-600 mt-1">
                    {{ $property->city }}, {{ $property->county }} · {{ $property->property_type }}
                </div>
            </div>

            @php($photos = $property->getMedia('photos'))
            @if ($photos->isNotEmpty())
                <div class="grid grid-cols-2 gap-2" data-testid="photos-gallery">
                    @foreach ($photos as $photo)
                        <img src="{{ $photo->getUrl('medium') }}" alt="{{ $property->title }}" class="w-full rounded-lg" />
                    @endforeach
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 mb-2">Descriere</h2>
                <p class="text-gray-700 whitespace-pre-line">{{ $property->description }}</p>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div><div class="text-gray-500">Oaspeți</div><div class="font-medium">{{ $property->max_guests }}</div></div>
                <div><div class="text-gray-500">Dormitoare</div><div class="font-medium">{{ $property->bedrooms }}</div></div>
                <div><div class="text-gray-500">Paturi</div><div class="font-medium">{{ $property->beds }}</div></div>
                <div><div class="text-gray-500">Băi</div><div class="font-medium">{{ $property->bathrooms }}</div></div>
            </div>

            @if ($property->amenities->isNotEmpty())
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="font-semibold text-gray-900 mb-3">Dotări</h2>
                    <ul class="grid grid-cols-2 gap-2 text-sm text-gray-700">
                        @foreach ($property->amenities as $amenity)
                            <li>· {{ $amenity->name_ro ?? $amenity->slug }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <aside class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm p-6 sticky top-6">
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-semibold">{{ number_format((float) $property->base_price, 0) }} {{ $property->currency }}</span>
                    <span class="text-gray-500 text-sm">/ noapte</span>
                </div>
                @if ($property->cleaning_fee > 0)
                    <div class="text-xs text-gray-500 mt-1">+ {{ number_format((float) $property->cleaning_fee, 0) }} {{ $property->currency }} taxă curățenie</div>
                @endif
                <div class="text-xs text-gray-500 mt-3">
                    Check-in: {{ $property->check_in_time }} · Check-out: {{ $property->check_out_time }}
                </div>
                <a href="{{ route('properties.book', $property) }}" class="mt-4 w-full inline-block text-center bg-gray-900 text-white py-2 rounded-md text-sm hover:bg-gray-800" data-testid="book-button">
                    Rezervă
                </a>
            </div>
        </aside>
    </div>
</div>
