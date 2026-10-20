<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'StayOps') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-slate-900 antialiased bg-white">
    @include('partials.impersonation-banner')
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/80 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2.5">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-orange to-brand-blue font-display text-sm font-extrabold text-white shadow-sm">S</span>
                <span class="font-display text-lg font-extrabold tracking-tight text-brand-navy">Stay<span class="text-brand-orange">Ops</span></span>
            </a>
            <nav class="flex items-center gap-4 sm:gap-6 text-sm font-medium">
                <a href="{{ route('properties.index') }}" class="text-slate-600 hover:text-brand-navy transition">Proprietăți</a>
                <a href="{{ url('/#cum-functioneaza') }}" class="hidden sm:inline text-slate-600 hover:text-brand-navy transition">Cum funcționează</a>
                @auth
                    @php($panelPath = \App\Support\RoleRedirector::pathFor(auth()->user()))
                    <a href="{{ route('dashboard') }}" class="hidden sm:inline text-slate-600 hover:text-brand-navy transition {{ request()->routeIs('dashboard') ? 'text-brand-navy' : '' }}">Dashboard</a>
                    @php($unreadMessages = auth()->user()->unreadMessagesCount())
                    <a
                        href="{{ route('messages.index') }}"
                        class="relative text-slate-600 hover:text-brand-navy transition {{ request()->routeIs('messages.*') ? 'text-brand-orange' : '' }}"
                        data-testid="nav-messages"
                    >
                        Mesaje
                        @if($unreadMessages > 0)
                            <span
                                class="absolute -top-2.5 -right-3 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-orange-500 px-1 text-[10px] font-bold leading-none text-white"
                                data-testid="nav-messages-badge"
                            >
                                {{ $unreadMessages > 9 ? '9+' : $unreadMessages }}
                            </span>
                        @endif
                    </a>
                    <livewire:notification-bell />
                    @if($panelPath !== '/dashboard')
                        <a href="{{ $panelPath }}" class="hidden sm:inline-flex items-center rounded-lg bg-brand-navy px-3.5 py-2 text-white hover:bg-slate-800 transition">Panoul meu</a>
                    @else
                        <a href="{{ route('profile.edit') }}" class="inline-flex items-center rounded-lg bg-brand-navy px-3.5 py-2 text-white hover:bg-slate-800 transition">Contul meu</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="text-slate-600 hover:text-brand-navy transition">Login</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-lg bg-brand-orange px-3.5 py-2 text-white shadow-sm hover:bg-brand-orange-600 transition">Înregistrare</a>
                @endauth
            </nav>
        </div>
    </header>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>
    <footer class="bg-brand-navy text-slate-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid gap-10 md:grid-cols-[1.5fr_1fr_1fr]">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-orange to-brand-blue font-display text-sm font-extrabold text-white">S</span>
                    <span class="font-display text-lg font-extrabold tracking-tight text-white">Stay<span class="text-brand-orange">Ops</span></span>
                </div>
                <p class="mt-3 font-display text-sm font-semibold text-slate-300">Rezervări. Operațiuni. Mentenanță.</p>
                <p class="mt-2 max-w-sm text-sm leading-relaxed">
                    Operating System for Short-Term Rentals — administrarea proprietăților în regim hotelier,
                    într-un flux complet automatizat.
                </p>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Platformă</h3>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="{{ route('properties.index') }}" class="hover:text-white transition">Proprietăți</a></li>
                    <li><a href="{{ url('/#cum-functioneaza') }}" class="hover:text-white transition">Cum funcționează</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-white transition">Înregistrare</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Cont</h3>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="{{ route('login') }}" class="hover:text-white transition">Login</a></li>
                    <li><a href="{{ route('dashboard') }}" class="hover:text-white transition">Dashboard</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500">
                <span>© {{ date('Y') }} {{ config('app.name', 'StayOps') }}. Toate drepturile rezervate.</span>
                <span>Regim hotelier &amp; mentenanță, într-un singur loc.</span>
            </div>
        </div>
    </footer>
</body>
</html>
