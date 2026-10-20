<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="font-display text-base font-bold text-gray-950">Necesită atenție</h3>
                <p class="mt-0.5 text-sm text-gray-500">
                    @if ($total > 0)
                        {{ $total }} elemente deschise care așteaptă o decizie.
                    @else
                        Totul este în regulă pe platformă.
                    @endif
                </p>
            </div>
            @if ($total > 0)
                <x-filament::badge color="danger" size="lg">
                    {{ $total }} deschise
                </x-filament::badge>
            @else
                <x-filament::badge color="success" size="lg">
                    În regulă
                </x-filament::badge>
            @endif
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            @foreach ($items as $item)
                <a
                    href="{{ $item['url'] }}"
                    @class([
                        'group flex items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-3 transition hover:border-primary-400 hover:shadow-sm dark:border-white/10 dark:bg-gray-900',
                        'opacity-60' => $item['count'] === 0,
                    ])
                >
                    <div class="flex items-center gap-3">
                        <span @class([
                            'inline-flex h-9 w-9 items-center justify-center rounded-lg',
                            'bg-warning-50 text-warning-600 dark:bg-warning-400/10' => $item['color'] === 'warning' && $item['count'] > 0,
                            'bg-danger-50 text-danger-600 dark:bg-danger-400/10' => $item['color'] === 'danger' && $item['count'] > 0,
                            'bg-gray-100 text-gray-500 dark:bg-white/5' => $item['count'] === 0,
                        ])>
                            <x-filament::icon
                                :icon="$item['count'] > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle'"
                                class="h-5 w-5"
                            />
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-gray-950">{{ $item['label'] }}</div>
                            <div class="text-xs text-gray-500">Deschide lista →</div>
                        </div>
                    </div>
                    <span @class([
                        'font-display text-2xl font-extrabold tabular-nums',
                        'text-warning-600' => $item['color'] === 'warning' && $item['count'] > 0,
                        'text-danger-600' => $item['color'] === 'danger' && $item['count'] > 0,
                        'text-gray-400' => $item['count'] === 0,
                    ])>
                        {{ $item['count'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
