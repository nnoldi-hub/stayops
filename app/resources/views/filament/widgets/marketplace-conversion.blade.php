<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-400/10">
                    <x-filament::icon icon="heroicon-o-funnel" class="h-5 w-5" />
                </span>
                <div>
                    <h3 class="font-display text-base font-bold text-gray-950">Conversie Marketplace</h3>
                    <p class="text-xs text-gray-500">De la înscriere la rezervare</p>
                </div>
            </div>
            <div class="flex items-center gap-4 text-right">
                <div>
                    <div class="font-display text-lg font-extrabold tabular-nums text-gray-950">{{ number_format($activationRate, 1) }}%</div>
                    <div class="text-xs text-gray-500">activare abonament</div>
                </div>
                <div>
                    <div class="font-display text-lg font-extrabold tabular-nums text-gray-950">{{ number_format($bookingRate, 1) }}</div>
                    <div class="text-xs text-gray-500">rezervări / gazdă activă</div>
                </div>
            </div>
        </div>

        <div class="mt-4 space-y-3">
            @foreach ($stages as $stage)
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-gray-800">{{ $stage['label'] }}</span>
                        <span class="text-sm tabular-nums text-gray-500">
                            <span class="font-semibold text-gray-950">{{ $stage['count'] }}</span>
                            <span class="text-xs">· {{ $stage['percent'] }}%</span>
                        </span>
                    </div>
                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                        <div @class([
                            'h-full rounded-full',
                            'bg-primary-500' => $stage['color'] === 'primary',
                            'bg-info-500' => $stage['color'] === 'info',
                            'bg-success-500' => $stage['color'] === 'success',
                            'bg-warning-500' => $stage['color'] === 'warning',
                        ]) style="width: {{ max(min($stage['percent'], 100), 3) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
