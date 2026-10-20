<x-filament-widgets::widget>
    <x-filament::section heading="Marketplace">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach ($sections as $section)
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-400/10">
                            <x-filament::icon :icon="$section['icon']" class="h-5 w-5" />
                        </span>
                        <h4 class="font-display text-sm font-bold text-gray-950">{{ $section['title'] }}</h4>
                    </div>
                    <dl class="mt-4 space-y-2.5">
                        @foreach ($section['rows'] as $row)
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-sm text-gray-500">{{ $row['label'] }}</dt>
                                <dd>
                                    <x-filament::badge :color="$row['color']">
                                        {{ $row['count'] }}
                                    </x-filament::badge>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
