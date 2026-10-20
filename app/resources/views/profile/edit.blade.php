<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-slate-900 leading-tight">
            Contul meu
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @php($completion = $user->profileCompletion())
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card" data-testid="profile-completion">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        @if($user->avatarUrl())
                            <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="h-14 w-14 rounded-full object-cover shadow" />
                        @else
                            <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-brand-orange to-brand-blue font-display text-lg font-extrabold text-white shadow">
                                {{ $user->initials() }}
                            </span>
                        @endif
                        <div>
                            <div class="font-display text-lg font-bold text-slate-900">{{ $user->name }}</div>
                            <div class="text-sm text-slate-500">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-display text-3xl font-extrabold text-brand-orange">{{ $completion }}%</div>
                        <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Profil completat</div>
                    </div>
                </div>

                <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-brand-orange transition-all" style="width: {{ $completion }}%"></div>
                </div>

                <ul class="mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-3" data-testid="profile-completion-steps">
                    @foreach($user->profileCompletionSteps() as $step)
                        <li class="flex items-center gap-2 text-sm {{ $step['done'] ? 'text-slate-700' : 'text-slate-400' }}">
                            @if($step['done'])
                                <svg class="h-4 w-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            @else
                                <svg class="h-4 w-4 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <circle cx="12" cy="12" r="9" />
                                </svg>
                            @endif
                            {{ $step['label'] }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow-card sm:rounded-2xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow-card sm:rounded-2xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow-card sm:rounded-2xl">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
