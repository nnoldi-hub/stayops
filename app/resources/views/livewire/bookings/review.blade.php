<?php

use App\Models\Booking;
use App\Models\Property;
use App\Models\Review;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] #[Title('Lasă un review')] class extends Component {
    public Booking $booking;
    public int $rating = 5;
    public ?int $cleanliness = null;
    public ?int $communication = null;
    public ?int $accuracy = null;
    public ?int $value = null;
    public string $comment = '';

    public function mount(Booking $booking): void
    {
        $user = auth()->user();
        // Doar guest-ul rezervării poate lăsa review.
        abort_unless($user && $booking->guest_id === $user->id, 403);
        // Doar după check-out.
        abort_unless($booking->status === 'checked_out', 403);
        // Un singur review per booking + author.
        $exists = Review::query()
            ->where('booking_id', $booking->id)
            ->where('author_id', $user->id)
            ->exists();
        abort_if($exists, 403, 'Ai trimis deja un review pentru această rezervare.');

        $booking->load('property');
        $this->booking = $booking;
    }

    public function save(): mixed
    {
        $data = $this->validate([
            'rating' => 'required|integer|min:1|max:5',
            'cleanliness' => 'nullable|integer|min:1|max:5',
            'communication' => 'nullable|integer|min:1|max:5',
            'accuracy' => 'nullable|integer|min:1|max:5',
            'value' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $user = auth()->user();
        $propertyId = (int) $this->booking->property_id;

        DB::transaction(function () use ($data, $user, $propertyId) {
            Review::create([
                'booking_id' => $this->booking->id,
                'author_id' => $user->id,
                'target_type' => Property::class,
                'target_id' => $propertyId,
                'rating' => $data['rating'],
                'cleanliness' => $data['cleanliness'] ?? null,
                'communication' => $data['communication'] ?? null,
                'accuracy' => $data['accuracy'] ?? null,
                'value' => $data['value'] ?? null,
                'comment' => $data['comment'] ?? null,
                'is_published' => true,
            ]);

            // Recalculează agregările pe proprietate.
            $agg = Review::query()
                ->where('target_type', Property::class)
                ->where('target_id', $propertyId)
                ->where('is_published', true)
                ->selectRaw('COUNT(*) AS cnt, AVG(rating) AS avg_rating')
                ->first();

            Property::where('id', $propertyId)->update([
                'reviews_count' => (int) ($agg->cnt ?? 0),
                'rating_avg' => round((float) ($agg->avg_rating ?? 0), 2),
            ]);
        });

        return redirect()->route('bookings.show', $this->booking);
    }
}; ?>

<div>
    <div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl mx-auto">
        <h1 class="text-2xl font-semibold text-gray-900">Lasă un review pentru {{ $booking->property->title }}</h1>
        <p class="text-sm text-gray-600 mt-1">Cod rezervare: <span class="font-mono">{{ $booking->reference }}</span></p>

        <form wire:submit="save" class="mt-6 space-y-4" data-testid="review-form">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nota generală (1–5)</label>
                <input type="number" min="1" max="5" wire:model.live="rating" class="mt-1 block w-32 rounded border-gray-300" data-testid="review-rating" />
                @error('rating') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Curățenie</label>
                    <input type="number" min="1" max="5" wire:model.live="cleanliness" class="mt-1 block w-32 rounded border-gray-300" />
                    @error('cleanliness') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Comunicare</label>
                    <input type="number" min="1" max="5" wire:model.live="communication" class="mt-1 block w-32 rounded border-gray-300" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Acuratețe</label>
                    <input type="number" min="1" max="5" wire:model.live="accuracy" class="mt-1 block w-32 rounded border-gray-300" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Raport calitate / preț</label>
                    <input type="number" min="1" max="5" wire:model.live="value" class="mt-1 block w-32 rounded border-gray-300" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Comentariu</label>
                <textarea wire:model.live="comment" rows="4" class="mt-1 block w-full rounded border-gray-300" data-testid="review-comment"></textarea>
                @error('comment') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="bg-indigo-600 text-white text-sm font-semibold px-4 py-2 rounded hover:bg-indigo-700" data-testid="submit-review">
                Trimite review
            </button>
        </form>
    </div>
</div>
