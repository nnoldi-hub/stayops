<?php

use App\Models\Conversation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] #[Title('Conversație')] class extends Component
{
    public Conversation $conversation;

    public string $body = '';

    public function mount(Conversation $conversation): void
    {
        $user = auth()->user();

        abort_unless($user !== null && $conversation->isParticipant($user), 403);

        $conversation->markReadFor($user);
        $conversation->load('users', 'booking');

        $this->conversation = $conversation;
    }

    public function send(): void
    {
        $this->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $user = auth()->user();
        abort_unless($user !== null && $this->conversation->isParticipant($user), 403);

        $this->conversation->sendMessage($user, $this->body);

        $this->body = '';
        $this->conversation->refresh();
    }

    public function markRead(): void
    {
        $user = auth()->user();

        if ($user !== null && $this->conversation->isParticipant($user)) {
            $this->conversation->markReadFor($user);
        }
    }
}; ?>

<div>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <a href="{{ route('messages.index') }}" class="text-sm font-medium text-orange-600 hover:text-orange-700">
                    ← Mesaje
                </a>
                <h1 class="mt-1 text-xl font-bold text-slate-900" data-testid="conversation-title">
                    {{ $conversation->titleFor(auth()->user()) }}
                </h1>
                @if($conversation->booking)
                    <a
                        href="{{ route('bookings.show', $conversation->booking) }}"
                        class="mt-0.5 inline-block text-sm font-medium text-orange-600 hover:text-orange-700"
                    >
                        Rezervare {{ $conversation->booking->reference }} →
                    </a>
                @endif
            </div>
        </div>

        <div
            class="flex h-[28rem] flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            wire:poll.15s="markRead"
        >
            <div class="flex-1 space-y-4 overflow-y-auto bg-slate-50 p-4" data-testid="message-list">
                @forelse($conversation->messages()->with('sender')->chronological()->get() as $message)
                    @php($isMine = $message->sender_id === auth()->id())
                    <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                        <div
                            class="max-w-[75%] rounded-2xl px-4 py-2.5 text-sm shadow-sm {{ $isMine ? 'bg-orange-500 text-white' : 'border border-slate-200 bg-white text-slate-700' }}"
                            data-testid="message-bubble"
                        >
                            @unless($isMine)
                                <div class="mb-0.5 text-xs font-semibold text-slate-500">
                                    {{ $message->sender?->name }}
                                </div>
                            @endunless
                            <p class="whitespace-pre-wrap break-words">{{ $message->body }}</p>
                            <div class="mt-1 text-[10px] {{ $isMine ? 'text-orange-100' : 'text-slate-400' }}">
                                {{ $message->created_at->diffForHumans(['short' => true]) }}
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="pt-16 text-center text-sm text-slate-400">
                        Niciun mesaj. Scrie primul mesaj pentru a începe conversația.
                    </p>
                @endforelse
            </div>

            <form wire:submit.prevent="send" class="flex items-center gap-2 border-t border-slate-200 bg-white p-3">
                <input
                    type="text"
                    wire:model="body"
                    placeholder="Scrie un mesaj…"
                    class="flex-1 rounded-full border-slate-300 px-4 py-2.5 text-sm focus:border-orange-500 focus:ring-orange-500"
                    data-testid="message-input"
                />
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="rounded-full bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-50"
                    data-testid="message-send"
                >
                    Trimite
                </button>
            </form>
            @error('body')
                <p class="px-4 pb-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
