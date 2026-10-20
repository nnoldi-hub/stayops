<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-400/10">
                    <x-filament::icon icon="heroicon-o-map" class="h-5 w-5" />
                </span>
                <div>
                    <h3 class="font-display text-base font-bold text-gray-950">Proprietăți după oraș</h3>
                    <p class="text-xs text-gray-500">{{ $totalCities }} orașe acoperite</p>
                </div>
            </div>
        </div>

        @if (empty($cities))
            <p class="mt-4 text-sm text-gray-500">Nicio proprietate înregistrată încă.</p>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($cities as $row)
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="truncate text-sm font-medium text-gray-800">{{ $row['city'] }}</span>
                            <span class="shrink-0 text-sm tabular-nums text-gray-500">
                                <span class="font-semibold text-gray-950">{{ $row['total'] }}</span>
                                <span class="text-xs">/ {{ $row['published'] }} publice</span>
                            </span>
                        </div>
                        <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                            <div class="h-full rounded-full bg-primary-500" style="width: {{ max($row['percent'], 4) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
