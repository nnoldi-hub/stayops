<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-5">
                <div @class([
                    'relative flex h-24 w-24 shrink-0 items-center justify-center rounded-full',
                    'bg-success-50 text-success-600 dark:bg-success-400/10' => $color === 'success',
                    'bg-warning-50 text-warning-600 dark:bg-warning-400/10' => $color === 'warning',
                    'bg-danger-50 text-danger-600 dark:bg-danger-400/10' => $color === 'danger',
                ])>
                    <span class="font-display text-3xl font-extrabold tabular-nums">{{ $score }}%</span>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">Status Platformă</div>
                    <div class="mt-1 flex items-center gap-2">
                        <x-filament::badge :color="$color" size="lg">
                            {{ $label }}
                        </x-filament::badge>
                    </div>
                    <p class="mt-2 max-w-sm text-sm text-gray-500">
                        Scor calculat din dispute deschise, sarcini restante, rezervări afectate și erori aplicație.
                    </p>
                </div>
            </div>

            <div class="grid flex-1 grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($factors as $factor)
                    @if ($factor['url'])
                        <a
                            href="{{ $factor['url'] }}"
                            class="rounded-xl border border-gray-200 bg-white px-4 py-3 transition hover:border-primary-400 hover:shadow-sm dark:border-white/10 dark:bg-gray-900"
                        >
                            @include('filament.widgets.partials.health-factor', ['factor' => $factor])
                        </a>
                    @else
                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-gray-900">
                            @include('filament.widgets.partials.health-factor', ['factor' => $factor])
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
