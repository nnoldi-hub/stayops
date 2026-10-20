<x-filament-widgets::widget>
    <x-filament::section heading="Activitate live">
        @if (empty($events))
            <p class="py-4 text-center text-sm text-gray-500">Nicio activitate recentă.</p>
        @else
            <ol class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($events as $event)
                    <li class="flex items-center gap-3 py-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-white/5">
                            <x-filament::icon :icon="$event['icon']" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-gray-950">{{ $event['label'] }}</div>
                            <div class="truncate text-xs text-gray-500">{{ $event['subject'] }}</div>
                        </div>
                        <time class="shrink-0 text-xs text-gray-400" datetime="{{ $event['at']->toIso8601String() }}">
                            {{ $event['at']->diffForHumans() }}
                        </time>
                    </li>
                @endforeach
            </ol>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
