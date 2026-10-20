<x-filament-panels::page>
    <div class="mb-3">
        <a href="{{ \App\Filament\Pages\Messages::getUrl() }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
            ← Mesaje
        </a>
        @if ($this->conversation->booking)
            <a
                href="{{ \App\Filament\Resources\Bookings\BookingResource::getUrl('view', ['record' => $this->conversation->booking]) }}"
                class="mt-0.5 ml-3 inline-block text-sm font-medium text-primary-600 hover:text-primary-700"
            >
                Rezervare {{ $this->conversation->booking->reference }} →
            </a>
        @endif
    </div>

    <x-filament::section>
        <div
            class="flex h-[26rem] flex-col overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900"
            wire:poll.15s="markRead"
        >
            <div class="flex-1 space-y-4 overflow-y-auto bg-gray-50 p-4 dark:bg-white/5" data-testid="message-list">
                @forelse ($this->conversation->messages()->with('sender')->chronological()->get() as $message)
                    @php($isMine = $message->sender_id === auth()->id())
                    <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                        <div
                            class="max-w-[75%] rounded-2xl px-4 py-2.5 text-sm shadow-sm {{ $isMine ? 'bg-primary-500 text-white' : 'border border-gray-200 bg-white text-gray-700 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200' }}"
                            data-testid="message-bubble"
                        >
                            @unless ($isMine)
                                <div class="mb-0.5 text-xs font-semibold text-gray-500">
                                    {{ $message->sender?->name }}
                                </div>
                            @endunless
                            <p class="whitespace-pre-wrap break-words">{{ $message->body }}</p>
                            <div class="mt-1 text-[10px] {{ $isMine ? 'text-primary-100' : 'text-gray-400' }}">
                                {{ $message->created_at->diffForHumans(['short' => true]) }}
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="pt-16 text-center text-sm text-gray-400">
                        Niciun mesaj. Scrie primul mesaj pentru a începe conversația.
                    </p>
                @endforelse
            </div>

            <form wire:submit.prevent="send" class="flex items-center gap-2 border-t border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-900">
                <input
                    type="text"
                    wire:model="body"
                    placeholder="Scrie un mesaj…"
                    class="flex-1 rounded-full border-gray-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800"
                    data-testid="message-input"
                />
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="rounded-full bg-primary-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-600 disabled:opacity-50"
                    data-testid="message-send"
                >
                    Trimite
                </button>
            </form>
            @error('body')
                <p class="px-4 pb-2 text-xs text-danger-600">{{ $message }}</p>
            @enderror
        </div>
    </x-filament::section>
</x-filament-panels::page>
