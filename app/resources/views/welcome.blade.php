<x-layouts.public title="{{ config('app.name', 'StayOps') }} — Rezervări. Operațiuni. Mentenanță.">
    @php
        $featured = \App\Models\Property::query()
            ->publiclyVisible()
            ->orderByDesc('rating_avg')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $stats = [
            'properties' => \App\Models\Property::query()->publiclyVisible()->count(),
            'cities' => (int) \App\Models\Property::query()->publiclyVisible()->distinct()->count('city'),
            'hosts' => \App\Models\Host::query()->withActiveSubscription()->count(),
            'tasks' => \App\Models\Task::query()->whereIn('status', ['completed', 'verified'])->count(),
        ];

        $cities = ['Brașov', 'București', 'Cluj-Napoca', 'Constanța', 'Sibiu', 'Timișoara'];

        $demoAccounts = [
            ['email' => 'admin@stayops.local', 'role' => 'SuperAdmin', 'panel' => '/admin'],
            ['email' => 'host@stayops.local', 'role' => 'Host', 'panel' => '/host'],
            ['email' => 'provider@stayops.local', 'role' => 'Provider', 'panel' => '/provider'],
            ['email' => 'guest@stayops.local', 'role' => 'Oaspete', 'panel' => '/dashboard'],
        ];

        $features = [
            [
                'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
                'title' => 'Rezervări & calendar',
                'text' => 'Căutare, rezervare și protecție anti-overbooking, cu sincronizare iCal Airbnb / Booking în timp real.',
                'tone' => 'orange',
            ],
            [
                'icon' => 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437',
                'title' => 'Operațiuni automate',
                'text' => 'Task de curățenie creat automat la check-out, alocat firmei potrivite, cu dovezi foto before/after.',
                'tone' => 'blue',
            ],
            [
                'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'title' => 'Mentenanță verificată',
                'text' => 'Sarcini de reparații și verificări periodice, cu flux de aprobare și istoric pe proprietate.',
                'tone' => 'orange',
            ],
            [
                'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
                'title' => 'Plăți & facturare',
                'text' => 'Stripe Connect pentru plăți către gazde, comision automat și raport de facturare lunar.',
                'tone' => 'blue',
            ],
        ];
    @endphp

    {{-- Hero --}}
    <section class="relative -mx-4 sm:-mx-6 lg:-mx-8 -mt-8 overflow-hidden bg-brand-navy text-white">
        <div class="pointer-events-none absolute inset-0 opacity-[0.35]"
             style="background-image: linear-gradient(rgba(255,255,255,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.06) 1px, transparent 1px); background-size: 48px 48px;"></div>
        <div class="pointer-events-none absolute -top-32 left-1/2 h-[480px] w-[720px] -translate-x-1/2 rounded-full blur-3xl"
             style="background: radial-gradient(closest-side, rgba(249, 115, 22, 0.35), transparent);"></div>
        <div class="pointer-events-none absolute -bottom-40 -right-24 h-[420px] w-[560px] rounded-full blur-3xl"
             style="background: radial-gradient(closest-side, rgba(37, 99, 235, 0.30), transparent);"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-24 lg:pt-20 lg:pb-32">
            <div class="max-w-3xl mx-auto text-center">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3.5 py-1.5 text-xs font-medium tracking-wide text-slate-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-orange"></span>
                    Operating System for Short-Term Rentals
                </span>
                <h1 class="mt-6 font-display text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.1]">
                    Rezervări. Operațiuni.<br class="hidden sm:block" />
                    <span class="bg-gradient-to-r from-brand-orange via-brand-orange-400 to-brand-blue bg-clip-text text-transparent">Mentenanță.</span>
                </h1>
                <p class="mt-6 text-base sm:text-lg leading-relaxed text-slate-300 max-w-2xl mx-auto">
                    StayOps transformă administrarea proprietăților în regim hotelier într-un flux complet
                    automatizat, de la rezervare și sincronizare calendar până la curățenie, mentenanță
                    și facturare, într-o singură platformă.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-xl bg-brand-orange px-6 py-3 font-semibold text-white shadow-lg shadow-brand-orange/25 hover:bg-brand-orange-600 transition">
                        Creează cont gratuit
                    </a>
                    <a href="{{ route('properties.index') }}" class="inline-flex items-center rounded-xl border border-white/20 bg-white/5 px-6 py-3 font-semibold text-white hover:bg-white/10 transition">
                        Vezi proprietățile
                    </a>
                </div>
            </div>

            <div class="mt-14 max-w-3xl mx-auto">
                <form action="{{ route('properties.index') }}" method="GET" class="rounded-2xl bg-white p-5 shadow-2xl shadow-black/40 ring-1 ring-white/10" data-testid="hero-search">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Caută o cazare</div>
                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Titlu / descriere"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-brand-orange focus:ring-brand-orange" />
                        <input type="text" name="city" value="{{ request('city') }}" placeholder="Oraș"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-brand-orange focus:ring-brand-orange" />
                        <select name="propertyType" class="w-full rounded-lg border-slate-300 text-sm focus:border-brand-orange focus:ring-brand-orange">
                            <option value="">Tip — toate</option>
                            @foreach (['apartment' => 'Apartament', 'house' => 'Casă', 'villa' => 'Vilă', 'studio' => 'Studio'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('propertyType') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="minGuests" min="1" value="{{ request('minGuests') }}" placeholder="Oaspeți minim"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-brand-orange focus:ring-brand-orange" />
                    </div>
                    <button type="submit" class="mt-4 w-full inline-flex justify-center items-center rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition">
                        Caută
                    </button>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($cities as $city)
                            <a href="{{ route('properties.index', ['city' => $city]) }}"
                                class="text-xs px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 hover:bg-brand-orange-50 hover:text-brand-orange-700 transition">
                                {{ $city }}
                            </a>
                        @endforeach
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- Funcționalități --}}
    <section class="mt-20">
        <div class="max-w-2xl mx-auto text-center">
            <h2 class="font-display text-2xl sm:text-3xl font-extrabold tracking-tight text-brand-navy">
                Un flux complet automatizat
            </h2>
            <p class="mt-3 text-slate-500">
                De la prima rezervare până la factura lunară — fiecare pas al operațiunii tale, în aceeași platformă.
            </p>
        </div>

        <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach ($features as $feature)
                <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-card hover:shadow-card-hover hover:-translate-y-0.5 transition">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $feature['tone'] === 'orange' ? 'bg-brand-orange-50 text-brand-orange' : 'bg-brand-blue-50 text-brand-blue' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}" />
                        </svg>
                    </span>
                    <h3 class="mt-4 font-display font-bold text-brand-navy">{{ $feature['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $feature['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Proprietăți recomandate --}}
    <section class="mt-20" data-testid="featured-properties">
        <div class="flex items-end justify-between mb-6">
            <div>
                <h2 class="font-display text-2xl font-extrabold tracking-tight text-brand-navy">Proprietăți recomandate</h2>
                <p class="text-sm text-slate-500 mt-1">Cazări verificate de gazde cu abonament activ.</p>
            </div>
            <a href="{{ route('properties.index') }}" class="text-sm font-semibold text-brand-orange hover:text-brand-orange-600 transition">Vezi toate →</a>
        </div>

        @if ($featured->isEmpty())
            <div class="rounded-2xl bg-slate-50 ring-1 ring-slate-200 p-12 text-center text-slate-500">
                Nicio proprietate publicată încă — rulează seed-ul demo pentru a adăuga date.
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($featured as $property)
                    @php($cover = $property->getFirstMediaUrl('photos', 'thumb'))
                    <a href="{{ route('properties.show', $property) }}"
                        class="group block rounded-2xl bg-white ring-1 ring-slate-200 overflow-hidden shadow-card hover:shadow-card-hover hover:-translate-y-0.5 hover:ring-brand-orange/60 transition">
                        <div class="aspect-[4/3] bg-gradient-to-br from-slate-100 to-slate-200">
                            @if ($cover)
                                <img src="{{ $cover }}" alt="{{ $property->title }}" class="w-full h-full object-cover" />
                            @endif
                        </div>
                        <div class="p-5">
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $property->city }} · {{ $property->property_type }}</div>
                            <h3 class="mt-1 font-display font-bold text-brand-navy group-hover:text-brand-orange transition">{{ $property->title }}</h3>
                            <div class="mt-2 text-sm">
                                <span class="font-semibold text-brand-navy">{{ number_format((float) $property->base_price, 0) }} {{ $property->currency }}</span>
                                <span class="text-slate-500"> / noapte · {{ $property->max_guests }} oaspeți</span>
                                @if ($property->reviews_count > 0)
                                    <span class="ml-2 font-medium text-brand-orange">★ {{ number_format((float) $property->rating_avg, 1) }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Cum funcționează --}}
    <section id="cum-functioneaza" class="mt-20 scroll-mt-24">
        <div class="max-w-2xl mx-auto text-center">
            <h2 class="font-display text-2xl sm:text-3xl font-extrabold tracking-tight text-brand-navy">Cum funcționează</h2>
            <p class="mt-3 text-slate-500">Trei roluri, un singur flux operațional.</p>
        </div>

        <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-5">
            @php($roles = [
                ['step' => '01', 'label' => 'Oaspete', 'title' => 'Caută și rezervă', 'items' => ['Căutare pe oraș, dotări și rază geografică', 'Rezervare cu protecție anti-overbooking', 'Confirmări pe email și review după check-out'], 'cta' => 'Caută cazare', 'href' => route('properties.index')],
                ['step' => '02', 'label' => 'Gazdă', 'title' => 'Închiriază cu încredere', 'items' => ['Verificare KYC + abonament obligatoriu', 'Conectare Stripe Connect și plăți automate', 'Sincronizare iCal (Airbnb / Booking) și panou de rezervări'], 'cta' => 'Devino gazdă', 'href' => route('register')],
                ['step' => '03', 'label' => 'Firmă mentenanță', 'title' => 'Lucrează eficient', 'items' => ['Task creat automat la check-out', 'Dovezi foto before/after și verificare', 'Raport de facturare lunar pe proprietar'], 'cta' => 'Înregistrează firma', 'href' => route('register')],
            ])
            @foreach ($roles as $role)
                <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-card">
                    <div class="flex items-center gap-3">
                        <span class="font-display text-sm font-extrabold text-brand-orange">{{ $role['step'] }}</span>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $role['label'] }}</span>
                    </div>
                    <h3 class="mt-3 font-display text-lg font-bold text-brand-navy">{{ $role['title'] }}</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        @foreach ($role['items'] as $item)
                            <li class="flex gap-2"><span class="text-brand-blue">✓</span> {{ $item }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ $role['href'] }}" class="mt-4 inline-block text-sm font-semibold text-brand-orange hover:text-brand-orange-600 transition">{{ $role['cta'] }} →</a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Cifre --}}
    <section class="mt-20">
        <div class="rounded-2xl bg-brand-navy text-white grid grid-cols-2 lg:grid-cols-4 divide-y divide-white/10 lg:divide-y-0 lg:divide-x overflow-hidden">
            @php($labels = ['properties' => 'Proprietăți publicate', 'cities' => 'Orașe', 'hosts' => 'Gazde active', 'tasks' => 'Sarcini finalizate'])
            @foreach ($stats as $key => $value)
                <div class="p-7 text-center">
                    <div class="font-display text-3xl sm:text-4xl font-extrabold text-brand-orange">{{ $value }}</div>
                    <div class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $labels[$key] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA final --}}
    <section class="mt-20 relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-navy via-brand-navy to-brand-blue/40 text-white p-10 lg:p-14">
        <div class="pointer-events-none absolute -top-24 -right-16 h-80 w-96 rounded-full blur-3xl"
             style="background: radial-gradient(closest-side, rgba(249, 115, 22, 0.35), transparent);"></div>
        <div class="relative flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
            <div class="max-w-2xl">
                <h2 class="font-display text-2xl sm:text-3xl font-extrabold tracking-tight">Gata să-ți digitalizezi operațiunea?</h2>
                <p class="mt-3 text-slate-300 leading-relaxed">
                    Creează un cont sau autentifică-te cu conturile demo pentru a explora panourile de gazdă,
                    firmă de mentenanță și administrare.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('login') }}" class="inline-flex items-center rounded-xl bg-white px-6 py-3 font-semibold text-brand-navy hover:bg-slate-100 transition">Login</a>
                <a href="{{ route('register') }}" class="inline-flex items-center rounded-xl bg-brand-orange px-6 py-3 font-semibold text-white shadow-lg shadow-brand-orange/25 hover:bg-brand-orange-600 transition">Înregistrare</a>
            </div>
        </div>
    </section>

    {{-- Conturi demo (doar local) --}}
    @if (app()->isLocal())
        <section class="mt-10 rounded-2xl bg-slate-50 ring-1 ring-slate-200 p-6" data-testid="demo-accounts">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Conturi demo (doar local)</div>
            <p class="text-sm text-slate-600 mt-1">Parolă pentru toate: <code class="px-1.5 py-0.5 rounded bg-white ring-1 ring-slate-200 font-mono text-xs">password</code></p>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach ($demoAccounts as $account)
                    <div class="bg-white rounded-xl ring-1 ring-slate-200 px-4 py-3 text-sm">
                        <div class="font-semibold text-brand-navy">{{ $account['role'] }}</div>
                        <div class="text-slate-600">{{ $account['email'] }}</div>
                        <div class="text-xs text-slate-400 mt-1">→ {{ $account['panel'] }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.public>
