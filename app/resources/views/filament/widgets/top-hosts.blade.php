<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-2.5">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-warning-50 text-warning-600 dark:bg-warning-400/10">
                <x-filament::icon icon="heroicon-o-trophy" class="h-5 w-5" />
            </span>
            <div>
                <h3 class="font-display text-base font-bold text-gray-950">Top Gazde (90 zile)</h3>
                <p class="text-xs text-gray-500">După numărul de rezervări</p>
            </div>
        </div>

        @if (empty($hosts))
            <p class="mt-4 text-sm text-gray-500">Nicio rezervare în ultimele 90 de zile.</p>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($hosts as $index => $host)
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex min-w-0 items-center gap-2">
                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600 dark:bg-white/10">
                                    {{ $index + 1 }}
                                </span>
                                <span class="truncate text-sm font-medium text-gray-800">{{ $host['name'] }}</span>
                            </span>
                            <span class="shrink-0 text-sm tabular-nums text-gray-500">
                                <span class="font-semibold text-gray-950">{{ $host['bookings'] }}</span>
                                <span class="text-xs">· {{ number_format($host['revenue'], 0) }} RON</span>
                            </span>
                        </div>
                        <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                            <div class="h-full rounded-full bg-warning-500" style="width: {{ max((int) round($host['bookings'] / $maxBookings * 100), 4) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
