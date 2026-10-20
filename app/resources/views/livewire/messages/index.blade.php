<?php

use App\Models\Conversation;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] #[Title('Mesaje')] class extends Component
{
    /**
     * @return Collection<int, array{conversation: Conversation, title: string, unread: int}>
     */
    #[Computed]
    public function entries(): Collection
    {
        $user = auth()->user();

        return Conversation::query()
            ->whereHas('users', fn ($query) => $query->where('users.id', $user->getKey()))
            ->with(['users', 'lastMessage.sender'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Conversation $conversation) => [
                'conversation' => $conversation,
                'title' => $conversation->titleFor($user),
                'unread' => $conversation->unreadCountFor($user),
            ])
            ->values();
    }
}; ?>

<div>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Mesaje</h1>
                <p class="mt-1 text-sm text-slate-500">Conversațiile tale cu oaspeții și gazdele.</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            @forelse($this->entries as $entry)
                @php($conversation = $entry['conversation'])
                <a
                    href="{{ route('messages.show', $conversation) }}"
                    class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 last:border-b-0 hover:bg-slate-50"
                    data-testid="conversation-row"
                >
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="truncate font-semibold text-slate-800">{{ $entry['title'] }}</span>
                            @if($entry['unread'] > 0)
                                <span
                                    class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-orange-500 px-1.5 text-[11px] font-bold text-white"
                                    data-testid="conversation-unread"
                                >
                                    {{ $entry['unread'] }}
                                </span>
                            @endif
                        </div>
                        @if($conversation->lastMessage)
                            <p class="mt-0.5 truncate text-sm text-slate-500">
                                <span class="text-slate-600">{{ $conversation->lastMessage->sender?->name }}:</span>
                                {{ $conversation->lastMessage->body }}
                            </p>
                        @else
                            <p class="mt-0.5 text-sm italic text-slate-400">Niciun mesaj încă.</p>
                        @endif
                    </div>
                    <span class="shrink-0 text-xs text-slate-400">
                        {{ ($conversation->last_message_at ?? $conversation->created_at)->diffForHumans() }}
                    </span>
                </a>
            @empty
                <div class="px-5 py-12 text-center">
                    <p class="text-sm text-slate-500">Nicio conversație încă.</p>
                    <a
                        href="{{ route('properties.index') }}"
                        class="mt-3 inline-block text-sm font-medium text-orange-600 hover:text-orange-700"
                    >
                        Găsește o proprietate →
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</div>
