<x-filament-widgets::widget>
    <x-filament::section heading="Flux operațional StayOps">
        <p class="text-sm text-gray-500">De la rezervare la facturare, în timp real.</p>

        <ol class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-stretch">
            @foreach ($steps as $index => $step)
                <li class="flex flex-1 items-center gap-3">
                    <div class="flex flex-1 flex-col items-center rounded-xl bg-gray-950 px-3 py-4 text-center text-white dark:bg-white/5">
                        <x-filament::icon :icon="$step['icon']" class="h-5 w-5 text-primary-400" />
                        <span class="mt-2 font-display text-2xl font-extrabold tabular-nums">{{ $step['value'] }}</span>
                        <span class="mt-1 text-xs font-medium text-gray-400">{{ $step['label'] }}</span>
                    </div>
                    @if (! $loop->last)
                        <x-filament::icon
                            icon="heroicon-m-arrow-right"
                            class="hidden h-4 w-4 shrink-0 text-gray-400 sm:block"
                        />
                    @endif
                </li>
            @endforeach
        </ol>
    </x-filament::section>
</x-filament-widgets::widget>
