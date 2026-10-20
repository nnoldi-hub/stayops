<?php

use App\Models\Property;
use App\Models\Provider;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.public')] #[Title('Editează proprietatea')] class extends Component {
    use WithFileUploads;

    public Property $property;

    public string $title = '';
    public string $description = '';
    public string $property_type = 'apartment';
    public string $address = '';
    public string $city = '';
    public string $county = '';
    public string $country = 'RO';
    public string $postal_code = '';
    public float $base_price = 0.0;
    public string $currency = 'RON';
    public float $cleaning_fee = 0.0;
    public int $max_guests = 1;
    public int $bedrooms = 0;
    public int $beds = 0;
    public int $bathrooms = 0;
    public int $min_nights = 1;
    public int $max_nights = 30;
    public string $status = 'draft';
    public ?int $preferredProviderId = null;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $newPhotos = [];
    public mixed $newFloorPlan = null;

    public function mount(Property $property): void
    {
        // Doar host-ul proprietății o poate edita.
        abort_unless($property->host?->user_id === auth()->id(), 403);

        $this->property = $property;
        $this->title = (string) $property->title;
        $this->description = (string) ($property->description ?? '');
        $this->property_type = (string) $property->property_type;
        $this->address = (string) $property->address;
        $this->city = (string) $property->city;
        $this->county = (string) ($property->county ?? '');
        $this->country = (string) ($property->country ?? 'RO');
        $this->postal_code = (string) ($property->postal_code ?? '');
        $this->base_price = (float) $property->base_price;
        $this->currency = (string) $property->currency;
        $this->cleaning_fee = (float) $property->cleaning_fee;
        $this->max_guests = (int) $property->max_guests;
        $this->bedrooms = (int) $property->bedrooms;
        $this->beds = (int) $property->beds;
        $this->bathrooms = (int) $property->bathrooms;
        $this->min_nights = (int) $property->min_nights;
        $this->max_nights = (int) $property->max_nights;
        $this->status = (string) $property->status;
        $this->preferredProviderId = $property->preferred_provider_id !== null
            ? (int) $property->preferred_provider_id
            : null;
    }

    public function availableProviders(): array
    {
        return Provider::query()
            ->where('is_active', true)
            ->where('kyc_status', 'approved')
            ->orderBy('company_name')
            ->pluck('company_name', 'id')
            ->all();
    }

    public function save(): mixed
    {
        $data = $this->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'property_type' => 'required|in:apartment,house,villa,studio,cabin',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:120',
            'county' => 'nullable|string|max:120',
            'country' => 'required|string|size:2',
            'postal_code' => 'nullable|string|max:20',
            'base_price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'cleaning_fee' => 'required|numeric|min:0',
            'max_guests' => 'required|integer|min:1|max:50',
            'bedrooms' => 'required|integer|min:0|max:20',
            'beds' => 'required|integer|min:0|max:50',
            'bathrooms' => 'required|integer|min:0|max:20',
            'min_nights' => 'required|integer|min:1',
            'max_nights' => 'required|integer|min:1',
            'status' => 'required|in:draft,published,paused,archived',
            'preferredProviderId' => 'nullable|integer|exists:providers,id',
        ]);

        $data['preferred_provider_id'] = $this->preferredProviderId;
        unset($data['preferredProviderId']);

        $this->property->update($data);

        return redirect()->route('host.dashboard');
    }

    public function uploadPhotos(): void
    {
        $this->validate([
            'newPhotos'   => 'array|max:10',
            'newPhotos.*' => 'image|mimes:jpeg,png,webp|max:5120',
        ]);

        foreach ($this->newPhotos as $photo) {
            $this->property
                ->addMedia($photo->getRealPath())
                ->usingFileName($photo->getClientOriginalName())
                ->toMediaCollection('photos');
        }

        $this->newPhotos = [];
        $this->dispatch('photos-uploaded');
    }

    public function uploadFloorPlan(): void
    {
        $this->validate([
            'newFloorPlan' => 'required|file|mimes:jpeg,png,webp,pdf|max:10240',
        ]);

        $this->property
            ->addMedia($this->newFloorPlan->getRealPath())
            ->usingFileName($this->newFloorPlan->getClientOriginalName())
            ->toMediaCollection('floor_plans');

        $this->newFloorPlan = null;
        $this->dispatch('floor-plan-uploaded');
    }

    public function deletePhoto(int $mediaId): void
    {
        $media = $this->property->getMedia('photos')->firstWhere('id', $mediaId);
        abort_unless($media !== null, 404);
        $media->delete();
    }

    public function deleteFloorPlan(): void
    {
        $this->property->clearMediaCollection('floor_plans');
    }
}; ?>

<div>
    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Editează proprietatea</h1>
            <a href="{{ route('host.dashboard') }}" class="text-sm text-gray-700 underline">Înapoi</a>
        </div>

        <form wire:submit="save" class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4" data-testid="edit-property-form">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Titlu</label>
                <input type="text" wire:model="title" class="mt-1 block w-full rounded border-gray-300" data-testid="field-title" />
                @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Descriere</label>
                <textarea wire:model="description" rows="3" class="mt-1 block w-full rounded border-gray-300"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Tip</label>
                <select wire:model="property_type" class="mt-1 block w-full rounded border-gray-300">
                    <option value="apartment">Apartament</option>
                    <option value="house">Casă</option>
                    <option value="villa">Vilă</option>
                    <option value="studio">Studio</option>
                    <option value="cabin">Cabană</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select wire:model="status" class="mt-1 block w-full rounded border-gray-300" data-testid="field-status">
                    <option value="draft">Draft</option>
                    <option value="published">Publicat</option>
                    <option value="paused">Pauză</option>
                    <option value="archived">Arhivat</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Adresă</label>
                <input type="text" wire:model="address" class="mt-1 block w-full rounded border-gray-300" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Oraș</label>
                <input type="text" wire:model="city" class="mt-1 block w-full rounded border-gray-300" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Județ</label>
                <input type="text" wire:model="county" class="mt-1 block w-full rounded border-gray-300" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Țară</label>
                <input type="text" wire:model="country" maxlength="2" class="mt-1 block w-full rounded border-gray-300" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Cod poștal</label>
                <input type="text" wire:model="postal_code" class="mt-1 block w-full rounded border-gray-300" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Preț / noapte</label>
                <input type="number" step="0.01" wire:model="base_price" class="mt-1 block w-full rounded border-gray-300" data-testid="field-base-price" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Taxă curățenie</label>
                <input type="number" step="0.01" wire:model="cleaning_fee" class="mt-1 block w-full rounded border-gray-300" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Firmă mentenanță preferată</label>
                <select wire:model.live="preferredProviderId"
                        class="mt-1 block w-full rounded border-gray-300"
                        data-testid="field-preferred-provider">
                    <option value="">— Nicio preferință (alocare automată) —</option>
                    @foreach ($this->availableProviders() as $providerId => $providerName)
                        <option value="{{ $providerId }}">{{ $providerName }}</option>
                    @endforeach
                </select>
                @error('preferredProviderId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Max oaspeți</label>
                <input type="number" wire:model="max_guests" class="mt-1 block w-full rounded border-gray-300" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Dormitoare</label>
                <input type="number" wire:model="bedrooms" class="mt-1 block w-full rounded border-gray-300" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Paturi</label>
                <input type="number" wire:model="beds" class="mt-1 block w-full rounded border-gray-300" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Băi</label>
                <input type="number" wire:model="bathrooms" class="mt-1 block w-full rounded border-gray-300" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Min nopți</label>
                <input type="number" wire:model="min_nights" class="mt-1 block w-full rounded border-gray-300" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Max nopți</label>
                <input type="number" wire:model="max_nights" class="mt-1 block w-full rounded border-gray-300" />
            </div>

            <div class="sm:col-span-2">
                <button type="submit" class="bg-indigo-600 text-white text-sm font-semibold px-4 py-2 rounded hover:bg-indigo-700" data-testid="submit-edit">
                    Salvează modificările
                </button>
            </div>
        </form>
        {{-- ── Gallery ─────────────────────────────────────────────────── --}}
        <div class="mt-8 border-t pt-6" data-testid="gallery-section">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Galerie foto</h2>

            {{-- Existing photos --}}
            @php $photos = $property->getMedia('photos'); @endphp
            @if($photos->isNotEmpty())
                <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 mb-4" data-testid="photos-grid">
                    @foreach($photos as $photo)
                        <div class="relative group" data-testid="photo-item-{{ $photo->id }}">
                            <img src="{{ $photo->getUrl('thumb') }}" alt="{{ $photo->name }}"
                                 class="w-full h-24 object-cover rounded" />
                            <button type="button"
                                    wire:click="deletePhoto({{ $photo->id }})"
                                    wire:confirm="Ștergi această fotografie?"
                                    data-testid="delete-photo-{{ $photo->id }}"
                                    class="absolute top-1 right-1 bg-red-600 text-white text-xs rounded px-1 py-0.5 opacity-0 group-hover:opacity-100 transition">
                                ×
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 mb-4" data-testid="no-photos">Nicio fotografie încă.</p>
            @endif

            {{-- Upload new photos --}}
            <form wire:submit="uploadPhotos" class="flex flex-col gap-2" data-testid="upload-photos-form">
                <input type="file" wire:model="newPhotos" multiple accept="image/jpeg,image/png,image/webp"
                       data-testid="photos-input"
                       class="text-sm text-gray-600" />
                @error('newPhotos.*') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <button type="submit"
                        data-testid="upload-photos-btn"
                        class="self-start text-sm bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700">
                    Încarcă fotografii
                </button>
            </form>
        </div>

        {{-- ── Floor plan ──────────────────────────────────────────────── --}}
        <div class="mt-6 border-t pt-6" data-testid="floor-plan-section">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Plan etaj</h2>

            @php $fp = $property->getFirstMedia('floor_plans'); @endphp
            @if($fp)
                <div class="flex items-center gap-4 mb-4" data-testid="floor-plan-current">
                    <img src="{{ $fp->getUrl('thumb') }}" alt="Plan etaj" class="h-20 w-auto rounded border" />
                    <button type="button"
                            wire:click="deleteFloorPlan"
                            wire:confirm="Ștergi planul etaj?"
                            data-testid="delete-floor-plan"
                            class="text-xs text-red-600 hover:underline">
                        Șterge
                    </button>
                </div>
            @else
                <p class="text-sm text-gray-400 mb-4" data-testid="no-floor-plan">Niciun plan etaj.</p>
            @endif

            <form wire:submit="uploadFloorPlan" class="flex flex-col gap-2" data-testid="upload-floor-plan-form">
                <input type="file" wire:model="newFloorPlan" accept="image/jpeg,image/png,image/webp,application/pdf"
                       data-testid="floor-plan-input"
                       class="text-sm text-gray-600" />
                @error('newFloorPlan') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <button type="submit"
                        data-testid="upload-floor-plan-btn"
                        class="self-start text-sm bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700">
                    Încarcă plan etaj
                </button>
            </form>
        </div>    </div>
</div>
