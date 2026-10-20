<x-filament-widgets::widget>
    <x-filament::section heading="Financiar — luna curentă">
        <dl class="space-y-3">
            @foreach ($rows as $row)
                <div class="flex items-center justify-between gap-3">
                    <dt class="flex items-center gap-2 text-sm text-gray-500">
                        <x-filament::icon :icon="$row['icon']" class="h-4 w-4 text-gray-400" />
                        {{ $row['label'] }}
                    </dt>
                    <dd class="text-sm font-semibold tabular-nums text-gray-950">{{ $row['value'] }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-4 flex items-center justify-between rounded-xl bg-primary-50 px-4 py-3 dark:bg-primary-400/10">
            <span class="text-sm font-semibold text-primary-700 dark:text-primary-300">Total platformă</span>
            <span class="font-display text-xl font-extrabold tabular-nums text-primary-700 dark:text-primary-300">
                {{ $total }}
            </span>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
