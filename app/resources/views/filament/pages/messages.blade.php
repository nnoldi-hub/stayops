<x-filament-panels::page>
    @php($entries = $this->getEntries())

    <x-filament::section>
        @if ($entries->isEmpty())
            <div class="py-10 text-center">
                <p class="text-sm text-gray-500">Nicio conversație încă.</p>
            </div>
        @else
            <div class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($entries as $entry)
                    @php($conversation = $entry['conversation'])
                    <a
                        href="{{ ViewConversation::getUrl(['conversation' => $conversation]) }}"
                        class="flex items-start justify-between gap-4 py-4 transition hover:bg-gray-50 dark:hover:bg-white/5"
                        data-testid="conversation-row"
                    >
                        <div class="min-w-0 px-1">
                            <div class="flex items-center gap-2">
                                <span class="truncate font-semibold text-gray-950">{{ $entry['title'] }}</span>
                                @if ($entry['unread'] > 0)
                                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary-500 px-1.5 text-[11px] font-bold text-white">
                                        {{ $entry['unread'] }}
                                    </span>
                                @endif
                            </div>
                            @if ($conversation->lastMessage)
                                <p class="mt-0.5 truncate text-sm text-gray-500">
                                    <span class="text-gray-600">{{ $conversation->lastMessage->sender?->name }}:</span>
                                    {{ $conversation->lastMessage->body }}
                                </p>
                            @else
                                <p class="mt-0.5 text-sm italic text-gray-400">Niciun mesaj încă.</p>
                            @endif
                        </div>
                        <span class="shrink-0 text-xs text-gray-400">
                            {{ ($conversation->last_message_at ?? $conversation->created_at)->diffForHumans() }}
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
