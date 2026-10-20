# PROGRES — MRHM

## Stare curentă (verificată local, 2026-10-19)

- **Teste**: 242/242 PASS (625 assertions) — `php artisan test`
- **Code style**: PASS — `php vendor/bin/pint --test`
- **Static analysis**: PASS (Larastan / PHPStan level 6, 0 erori) — `php vendor/bin/phpstan analyse`
- **Migrații**: 24 OK pe baza `mrhm`
- **CI**: `.github/workflows/ci.yml` pe MySQL 8.4 (fără PostGIS), Redis, Pin+PHPStan+Teste
- Repo: fără commit-uri noi (nu s-a cerut explicit).
- Notă mediu: MySQL/Redis local nu pornesc cu sistemul — pornite manual din `C:\n\laragon\bin\`; Bitdefender Endpoint Security scanează ocazional executabilele noi (episoade de ~15-60s/proces, trec de la sine).

## Decizii tehnice

- **DB: MySQL nativ Laragon 8.4.3** (nu PostgreSQL/PostGIS). Geo-căutare prin **formulă Haversine** (`whereRaw`), fără `ST_DWithin`; anti-overbooking prin `lockForUpdate` în tranzacție, fără constrângere `EXCLUDE`.
- **Redis 5.0.14.1** + client **predis** (`phpredis` DLL lipsește pe Windows).
- **PHP 8.3.30** local; `composer.json` are `platform.php=8.3.30`.
- **Horizon incompatibil pe Windows** (`ext-pcntl`/`ext-posix`); local se folosește `queue:work`. În CI (Linux) Horizon poate rula.
- **Filament 5.10.2** — 2 panouri: `admin` (SuperAdmin) și `provider` (Provider). Panoul Host folosește Livewire/Volt (decizie existentă, păstrată).

## Istoric

### Sesiunea 1 — Baseline, portare MySQL, quality gates

- Baseline restaurat: `git restore .` (HEAD `0f51f7b`).
- Mediu local: PHP 8.3.30, MySQL 8.4.3 (3306), Redis (6379). DB create: `mrhm`, `testing`.
- Portare DB → MySQL:
  - Șters migrația PostGIS `2026_05_26_069999_enable_postgis_extension.php`.
  - `properties`: `location geography(POINT,4326)` + GIST → `latitude`/`longitude decimal(10,7)` + index compus.
  - `Property` (model): `latitude`/`longitude` în fillable + casts `decimal:7`.
  - `properties.index` (Livewire): `ilike` → `like`; căutare pe rază prin Haversine cu guard `LEAST/GREATEST`.
  - `PropertyGeoSearchTest` adaptat la lat/lng. `phpunit.xml` configurat pentru MySQL (`testing`).
- Deps: `composer update` cu platform PHP 8.3.30 → Laravel 13.37.0, Livewire 4.4.7, Filament 5.10.2. `predis/predis ^3.7`.
- Frontend: `npm ci` + `npm run build` (creat `public/build/manifest.json`).
- Config: `.env` (MySQL/predis) + `.env.example` actualizat.
- CI migrat Postgres+PostGIS → MySQL 8.4; adăugat `REDIS_CLIENT=predis`.
- Fixuri teste (3 eșecuri inițiale, cauze la nivel de test):
  - `PublicBookingFlowTest` — date dinamice (`now()->addDays(...)`).
  - `HostDashboardCrudTest::test_host_cannot_delete_another_hosts_property` + `HostBookingTransitionsTest::test_host_cannot_confirm_booking_of_another_host` — `withoutExceptionHandling()` (Livewire 4.4.7: `ModelNotFoundException` → 404 prin handler).
- Pint aplicat pe întreg codebase.

### Sesiunea 2 — Panoul Filament Provider + curățenie static analysis

**Panoul Provider (al 3-lea panou din plan):**
- `app/Providers/Filament/ProviderPanelProvider.php` — panou `provider` la `/provider`, login, culoare Emerald, widget cont, middleware + `EnsureTwoFactorAuthenticated`.
- `app/Filament/Provider/Resources/TaskResource.php` — resursă „Sarcini" scoped la sarcinile furnizorului curent (`assigned_to_user_id` = user curent SAU `provider.user_id` = user curent); `canCreate`/`canDelete` = false.
- `app/Filament/Provider/Resources/Tasks/{Pages,Schemas,Tables}` — `ListTasks`, `EditTask`, form (status/cost/note), tabel cu acțiuni `start`/`complete` (tranziții status).
- `User::canAccessPanel()` devine role-aware: `admin` → SuperAdmin, `provider` → Provider.
- `User::provider()` (HasOne) adăugat.
- Șters ruta placeholder `/provider` din `routes/web.php` (era `view('dashboard')`); `RoleRedirector` trimite deja Provider la `/provider`.
- `ProviderFactory`, `TaskFactory` create.
- `tests/Feature/ProviderPanelTest.php` — 5 teste: acces Provider, 403 pentru non-Provider, redirect guest, scoping pe `provider_id`, scoping pe `assigned_to_user_id`.

**Extindere panou Provider (widget-uri + filtre):**
- `Task::scopeVisibleToProviderUser(int|string|null $userId)` — scope reutilizabil (assigned direct SAU `provider.user_id`); folosit de `TaskResource` și widget-uri (DRY).
- `app/Filament/Provider/Widgets/TaskStatsWidget.php` — card de statistici pe dashboard: „De început" (open+assigned), „În lucru" (in_progress), „Finalizate" (completed+verified), scoped la furnizor.
- `app/Filament/Provider/Widgets/RecentCompletedTasksWidget.php` — `TableWidget` „Istoric recent (finalizate)": completed/verified/cancelled, paginat 5/10/25, cu status badge și cost real.
- Filtre în `TasksTable`: `SelectFilter` status / tip / prioritate.
- Widget-urile înregistrate în `ProviderPanelProvider::widgets()`.
- `TaskResource` adnotat `@extends \Filament\Resources\Resource<Task>` (necesar ca Larastan să rezolve scope-ul pe `Builder<Task>`).
- Teste adăugate în `ProviderPanelTest` (5): dashboard 200 + nav, widget-uri înregistrate, stats counts (10/11/18 scoped), istoric recent listează doar finalizate, filtrare tabelă pe status.
- Notă: widget-urile de dashboard se randează ca **Livewire lazy** → conținutul lor nu apare în HTML-ul inițial al paginii; testarea se face cu `Livewire::test(...)`, nu `assertSee` pe pagină.

### Sesiunea 3 — Auto-task curățenie la check-out (Etapa 3 din plan)

**Dispecerat automat:** check-out-ul unei rezervări creează automat task-ul de curățenie.
- `app/Events/BookingCheckedOut.php` — eveniment trimis din `BookingObserver::updated` la orice tranziție `→ checked_out` (indiferent de calea: Volt host, Filament admin, BookingService).
- `app/Listeners/CreateCleaningTask.php` — listener înregistrat explicit în `AppServiceProvider::boot`:
  - **Idempotent**: nu creează al doilea task dacă există deja unul (ne-anulat) pe aceeași rezervare; un task `cancelled` nu blochează recrearea.
  - **Alocare provider** (regulile din plan „alocă-l Firmei X"):
    1. `properties.preferred_provider_id` — firma preferată setată de host pe proprietate (doar dacă `is_active` + KYC `approved`);
    2. fallback: primul provider activ/approved cu `service_types` conținând `cleaning` și `coverage_cities` acoperind orașul proprietății (match case-insensitive; `coverage_cities` gol = acoperire națională/universală);
    3. niciunul → task `open` fără provider (apare în listă pentru atribuire manuală).
  - `scheduled_at` = data check-out-ului + `properties.check_out_time`.
- **Migrație** `2026_10_18_000001_add_preferred_provider_to_properties_table` → `properties.preferred_provider_id` FK nullable (`nullOnDelete`).
- `Property::preferredProvider()` + `preferred_provider_id` în fillable; adnotare `@return BelongsTo<Property, $this>` pe `Booking::property()` (PHPStan).
- **UI Host**: select „Firmă mentenanță preferată” în `host/properties/edit` (doar provideri activi + KYC approved, opțiunea goală = alocare automată), validare `exists:providers,id`.
- **Filament admin**: `Select preferred_provider_id` (relationship, searchable, preload) în `PropertyForm`.
- `tests/Feature/CheckoutCleaningTaskTest.php` — 12 teste: dispatch eveniment, task open fără provider, alocare la firma preferată, fallback pe oraș când preferatul e inactiv/neaprobat, match case-insensitive + excludere non-cleaning, provider cu acoperire goală, idempotență (check-out repetat), recreare după cancel, `scheduled_at` = check-out time, setare/clearare preferință din UI, tranziții non-check-out nu creează taskuri.
- Decizie: chiar dacă listenerul e și auto-discoverabil (`app/Listeners`), e înregistrat **explicit** în `AppServiceProvider` — comportament determinist, fără surprize la rename.
- `Booking::property()` adnotat cu generică — necesar ca Larastan să rezolve `$booking->property->city` etc.
- Notă: `test_task_is_scheduled_at_property_check_out_time` acoperă integrarea `check_out_time` (coloana `time` MySQL → string `HH:MM:SS`).

**Mediu (sesiunea 3):**
- MySQL 8.4.3 + Redis 5.0.14.1 pornite manual (nu sunt servicii Windows); datadir MySQL = `C:\n\laragon\data\mysql-8.4`.
- Episod de lentinoare cauzat de **Bitdefender Endpoint Security Tools** (scanare executabile noi: `php artisan` 20-70s, `redis-cli` 15s) — nu e codul; a trecut de la sine, testele au rulat 69s după.

**Fixuri static analysis (PHPStan level 6 → 0 erori):**
- `phpstan.neon`: eliminat opțiuni deprecated; adăugat `treatPhpDocTypesAsCertain: false`; `ignoreErrors` pe identificatorii `missingType.generics` / `missingType.iterableValue` (echivalentul modern al vechilor `checkGenericClassInNonGenericObjectType`/`checkMissingIterableValueType`).
- `VerifyEmailController` — folosește `$request->fulfill()` (declanșează `Verified`, idiomatic; elimină nepotrivirea de tip `App\Models\User` vs contract `MustVerifyEmail`).
- `Host::user()` — adnotat `@return BelongsTo<User, $this>` (rezolvă `$host->user->email`).
- `Booking::canTransitionTo()` — guard defensiv păstrat, compatibil PHPStan.
- `Property::registerMediaConversions()` — reordonat `->nonQueued()->fit(...)` (compatibilitate `@mixin ImageDriver`).
- Stripe: valori `metadata` castate la `string`.
- `routes/web.php` — guard-uri tip pentru `$host`; `routes/console.php` — eliminat scaffold-ul `inspire`.
- `Filament\Provider\...\TaskResource::canDelete(Model $record)` — tip de parametru.

### Sesiunea 4 — Echipe furnizor, Proof of Work, Facturare (documentată retroactiv)

Cod existent în repo, verificat și acoperit de teste (11 teste, toate verzi):
- **Echipe interne (`Teams`)**: `app/Filament/Provider/Resources/Teams/` — `TeamResource` scoped pe furnizorul curent, CRUD echipe, `MembersRelationManager` (adăugare/ștergere membri cu rol `lead`/`member`); delegare task către membru prin `assigned_to_user_id` în `TaskForm`.
- **Proof of Work**: `Task` cu colecțiile MediaLibrary `before_photos` / `after_photos`; acțiunea `complete` din `TasksTable` refuză finalizarea fără poze „după" (notificare de eroare), completează `completed_at`.
- **Facturare (`Invoicing`)**: pagină Filament `Provider\Pages\Invoicing` (`/provider/invoicing`) — raport lunar al task-urilor `completed`/`verified`, filtre pe proprietar și lună, sumar `actual_cost`.
- Teste: `ProviderTeamManagementTest` (5), `ProviderTaskProofOfWorkTest` (3), `ProviderInvoicingTest` (5).

### Sesiunea 6 — Teste + fix `Invoicing`

- Adăugat `tests/Feature/ProviderInvoicingTest.php` (5 teste): acces panou, interzicere alt rol, doar task-uri `completed`/`verified` ale furnizorului, filtru pe proprietar, filtru pe lună.
- **Bug real găsit și reparat** în `Provider\Pages\Invoicing`: filtrele `SelectFilter` foloseau `query(fn (Builder $query, ?string $value) => ...)`, dar Filament injectează starea filtrului **după numele parametrului** (`$state`/`$data`), nu după poziție → `$value` era mereu `null`, deci filtrul pe proprietar/lună nu funcționa niciodată (returna 0 rânduri). Reparat cu `array $state` + `when(filled($state['value'] ?? null), ...)`.

### Sesiunea 5 — SuperAdmin: Impersonation + KYC approval workflow

**Impersonation („Intră în cont"):**
- `app/Services/ImpersonationService.php` — start/stop + `state()`; reguli: doar `users.impersonate` (SuperAdmin), nu pe sine, nu pe alt SuperAdmin, nu peste o impersonare activă; scrie `impersonation_logs` (admin, target, motiv, IP, UA, `started_at`); `Auth::login($target)` + `session()->regenerate()`; la stop setează `ended_at` și restaurează adminul.
- `app/Http/Controllers/ImpersonationController.php` + rute: `POST /impersonation/{user}` (`permission:users.impersonate` + `require_2fa`), `POST /impersonation/stop`, `GET /impersonation/landing` (redirect rol-aware prin `RoleRedirector`).
- `app/Filament/Actions/ImpersonateAction.php` — acțiune reutilizabilă de tabel (modal cu motiv), `successRedirectUrl` → landing; folosită în `HostsTable` și `ProvidersTable`.
- **Banner** `resources/views/partials/impersonation-banner.blade.php` cu buton „Ieși din impersonare"; injectat în toate panourile Filament prin `FilamentView::registerRenderHook(PanelsRenderHook::BODY_START)` (în `AppServiceProvider`) și în layout-ul public (paginile Volt Host).
- `EnsureTwoFactorAuthenticated` sare peste cerința de 2FA cât timp există impersonare activă — adminul nu e forțat în fluxul de setup 2FA al contului țintă (evită și setarea accidentală a 2FA pe contul victimei).
- Teste: `SuperAdminImpersonationTest` (11) — start/stop, permisiuni, self/alt admin interzis, dublă impersonare interzisă, banner, `.withoutExceptionHandling` nu e nevoie (rutele întorc 403 curat), acțiune Filament.

**KYC workflow:**
- Admin: `HostsTable` + noua resursă `Providers\ProviderResource` (`/admin/providers`) primesc acțiunile `ApproveKycAction` / `RejectKycAction` (confirmare, vizibilitate condiționată de permisiunile `hosts.approve_kyc` / `providers.approve_kyc`), coloane `kyc_status` ca badge colorat.
- Formulare admin: `kyc_status` devine `Select` cu etichete RO; `kyc_documents` devine `FileUpload` multiple pe discul `public`.
- **Trimitere documente**:
  - Host: pagină Volt `/host/verification` (`livewire/host/verification.blade.php`) — upload multiplu (JPG/PNG/PDF ≤5MB), status afișat, setează `kyc_status=submitted`; link + badge de status în dashboard.
  - Provider: pagină Filament `/provider/verification` (`app/Filament/Provider/Pages/Verification.php`) cu acțiune de header pentru upload + submit.
- **Gating public**: `Property::scopePubliclyVisible()` (publicat + host cu `kyc_status=approved`) folosit în `/properties`; `properties.show` și `properties.book` întorc 404 pentru proprietățile gazdelor neverificate.
- Teste: `KycApprovalTest` (8) — approve/reject host+provider, audit (cu `audit.console=true`, altfel pachetul nu auditează în CLI), acces resursă admin; `KycVerificationTest` (9) — submit host (Volt) + provider (acțiune Filament), validare, link dashboard, vizibilitate publică.

**Fixuri/decizii sesiunea 5:**
- `Provider::user()` adnotat `@return BelongsTo<User, $this>` (PHPStan: `Model::hasRole()` pe relația nerexplicită).
- Filament 5: `TextColumn::boolean()` nu există → `IconColumn::make(...)->boolean()`; `TextColumn::numeric(decimals: ...)` → `numeric(2)`; `TextColumn::separator()` absent → `formatStateUsing()`.
- `php -r` a confirmat că `?A|?B` e sintaxă invalidă (uniune nulabilă se scrie `A|B|null`).
- Ordinea rutelor contează: `POST /impersonation/stop` înaintea `POST /impersonation/{user}`.

### Sesiunea 7 — Modulul SaaS (Cashier): abonamente forțate, application_fee, webhook, widget MRR/churn

**Abonamente forțate pe host:**
- `App\Http\Middleware\EnsureHostSubscription` (alias `subscription` în `bootstrap/app.php`) — verifică `User::hasActiveHostSubscription()`; rutele `/host/*` stau în grupul `['auth','verified','role:Host','require_2fa','subscription']`, cu excepția rutei `Volt::route('/host/subscription', ...)` (în afara gating-ului, ca să poți plăti).
- `User::{hasActiveHostSubscription(), activeHostSubscription(), activeHostPlan()}` (plan rezolvat prin `subscriptions.stripe_price`); `Host::{hasActiveSubscription(), activePlan(), platformFeePct(), scopeWithActiveSubscription()}` — fallback comision = `config('services.stripe.platform_fee_pct')`.
- `Property::scopePubliclyVisible()` extins: pe lângă `kyc_status=approved`, gazda trebuie să aibă **abonament activ** (gating și pentru `properties.show`/`book`).
- `BookingService::create()` aruncă `RuntimeException` („Rezervare refuzata: ... nu apartine unei gazde cu abonament activ") la începutul tranzacției.
- `HostSubscriptionService` — `startCheckout()` (Stripe Checkout `mode: subscription`, validare `is_active` + `stripe_price_id` gol → `InvalidArgumentException`), `cancel()` (Cashier `cancel()` = cancel at period end), `resolveCustomerId()`; `stripe()` **lazy** (`return Cashier::stripe();` — injectarea în constructor a fost eliminată: obliga cheie Stripe peste tot).
- Pagina Volt `/host/subscription` (`resources/views/livewire/host/subscription.blade.php`): planuri, status, `subscribe($planId)`, `cancel()`, `?checkout=success|cancelled`, `data-testid`-uri; link „Abonament" în `host/dashboard.blade.php`.

**Platform fee la rezervări:**
- `BookingPaymentService::createPaymentIntent()` — `application_fee_amount` (procent din planul gazdei → fallback config) + `transfer_data.destination` doar dacă gazda are `stripe_account_id` și fee > 0; `customer` = `$guest->createOrGetStripeCustomer()->id` (Cashier creează/ia clientul Stripe).
- `BookingService` primește `BookingPaymentService` prin constructor (autowire, fără binding); blocul Stripe inline a fost înlocuit cu apelul de serviciu în try/catch-ul existent (eroarea se loghează, rezervarea se creează).
- Config: `config/services.php` → `stripe` (`webhook_secret`, `platform_fee_pct`), `config/cashier.php` minimal, `.env.example` + `STRIPE_PLATFORM_FEE_PCT=10`.

**Webhook + metrici admin:**
- `StripeWebhookController` primește branch `customer.subscription.*` → `syncSubscription()`: `Cashier::findBillable()` (doar `User`), `updateOrCreate` pe `stripe_id`, `update` pe `deleted`/`stripe_status`/`stripe_price`/`quantity`/`ends_at` (din `items.data[0].current_period_end ?? current_period_end`), `Log::warning` pentru customer necunoscut.
- `App\Services\SubscriptionMetrics` — `activeQuery()`, `activeCount()`, `mrr()` (abonamente anuale / 12), `arr()`, `churn(30)` → `['canceled','rate']`.
- `App\Filament\Widgets\SubscriptionMetricsWidget` (`StatsOverviewWidget`, `sort -20`): Stat „MRR", „Abonamente active", „Churn 30 zile".

**Fixuri/găsiri sesiunea 7:**
- `Host::factory()` creează **implicit** abonament activ (`Plan::firstOrCreate(slug: 'standard')` + `subscriptions()->firstOrCreate(stripe_id: sub_host_{user_id})`), precum `kyc_status => approved`; negativele se scriu explicit: `withoutSubscription()` (șterge) / `unsubscribed()` (status `canceled` + `ends_at` +14 zile). `PlanFactory` nou (`yearly()`, `inactive()`, `stripe_price_id`).
- **Cum se mock-uiește Stripe în teste**: `Cashier::stripe()` apelează `app(StripeClient::class, ['config' => ...])`, iar `Container::resolve()` **sare peste instance-legate** când `$needsContextualBuild` (parametri ne-goli) → `$this->app->instance(StripeClient::class, $mock)` nu funcționează („api_key cannot be the empty string"). Corect: `$this->app->bind(StripeClient::class, fn () => $mock)` — binding buildable, ignoră parametrii contextuali.
- `Volt::test(...)` **nu execută** middleware-ul de rută → gating-ul (2FA/abonament) se testează cu `$this->get(route(...))`; `BookingService::create()` necesită atribute complete (`nights`, `nightly_rate`, `subtotal`, `total_price` NOT NULL).
- `Property::host()` adnotat `@return BelongsTo<Host, $this>` → permisă eliminarea hack-urilor `method_exists` din `BookingService` / `BookingPaymentService`.
- `BookingService.php` linia ~62 conține caractere mojibake pre-existente (U+FFFD, bytes `EF BF BD`, UTF-8 valid) — **nu se ating** (edit-ul pe linia aia eșuează intenționat).
- Teste noi (35): `HostSubscriptionGatingTest` (10), `HostSubscriptionPageTest` (8), `SubscriptionMetricsTest` (7), `StripeSubscriptionWebhookTest` (6), `ApplicationFeeTest` (4).

**Gate-uri (2026-10-19):** 242/242 PASS (625 assertions, ~195s), Pint PASS, PHPStan level 6 → 0 erori.

## Sesiunea 8 — Rebranding StayOps + landing public + demo + Command Center SuperAdmin

**Rebranding + landing (finalizat, verificat):**
- Identitate: „StayOps — Rezervări. Operațiuni. Mentenanță.", poziționare „Operating System for Short-Term Rentals"; culori Orange `#F97316` / Blue `#2563EB` / Navy `#0F172A` / Slate `#64748B`; fonturi Plus Jakarta Sans (display) + Inter (body), încărcate de pe fonts.bunny.net.
- `tailwind.config.js` (brand.orange/blue/navy + scale slate, font-sans/font-display, shadow card); `APP_NAME=StayOps` (.env + .env.example); emailuri demo migrate `@mrhm.local` → `@stayops.local` (8 users + 2 bookings, script tinker șters după rulare); `AdminUserSeeder` → `admin@stayops.local`; fotografii demo regenerate cu text „StayOps"; 2FA secret `stayops-demo-...`.
- `welcome.blade.php` rescris (hero navy, badge poziționare, 4 features, proprietăți, cum funcționează, stats, CTA, conturi demo); header/footer StayOps în `layouts/public.blade.php`; testid-uri (`featured-properties`, `demo-accounts`, `hero-search`) păstrate.
- `DemoDataSeeder` idempotent + extins: emailuri `@stayops.local`, gardă `DatabaseSeeder` pe `app()->isLocal()`; `php artisan storage:link` ok.

**Command Center SuperAdmin (`/admin`, finalizat):**
- Pagină custom `App\Filament\Pages\Dashboard` (extend `Filament\Pages\Dashboard`): titlu/nav „Command Center", heading „Bun venit, {nume} 👋", subheading cu `now()->translatedFormat('d F Y')`, `getColumns(): 6`, `getWidgets()` cu ordine explicită; `Filament::getWidgets()` default e înlocuit per pagină.
- 8 widgeturi (`app/Filament/Widgets/`): `KpiOverviewWidget` (sort -100, colSpan 6, 6 statistici: MRR/ARR/gazde/proprietăți/rezervări/taskuri), `NeedsAttentionWidget` (KYC, Stripe, dispute, taskuri întârziate → linkuri `/admin/hosts|disputes|tasks`), `MrrGrowthChartWidget` (line, `SubscriptionMetrics::mrrSeries(12)`), `BookingsChartWidget` (bar, check_in 12 luni), `FinancialSummaryWidget` (Venit SaaS/comisioane rezervări/mentenanță/total), `OperationalFlowWidget` (fluxul: rezervări active → check-out azi → taskuri create azi → finalizate azi → facturi), `MarketplaceOverviewWidget` (Gazde/Proprietăți/Firme+Echipe), `LiveActivityWidget` (polling 60s, merge 4 surse, sort desc, limit 8). Vizualizări în `resources/views/filament/widgets/*.blade.php` (wrapper `<x-filament-widgets::widget>` + `<x-filament::section>`).
- Meniu grupat: `->navigationGroups(['Marketplace','Operațional','Financiar','Configurare'])` + `PlanResource` mutat în „Financiar" ca „Abonamente" (icon CreditCard); panou: `->brandName('StayOps')`, `->colors(['primary' => Color::Orange])`; `Filament::getWidgets()` default (AccountWidget/FilamentInfoWidget) eliminat — widgeturile se descoperă automat și se filtrează pe pagină.
- `DemoDataSeeder::seedCommandCenterData()`: abonamente gazde backdate 9/5/2 luni (curbă MRR), 8 rezervări istorice (created_at = check_in − 2 zile) + 1 check-out azi, 4 taskuri întârziate + 3 finalizate recent cu `actual_cost`, 2 proprietăți draft, 3 dispute (open/investigating/resolved).

**Rețeaua de build (găsire importantă — Filament v5 = Tailwind v4, aplicația = Tailwind v3):**
- **Nu se poate build-ui un theme Filament în proiecte Tailwind v3**: `@import 'tailwindcss' source(none)` din `vendor/filament/.../resources/css/theme.css` e rezolvat de postcss-import ca fișier local („Unknown word use strict"); instalarea `@tailwindcss/vite` nu ajută fiindcă `postcss.config.js` global (v3) interceptează orice CSS build-uit → „use tailwindcss directly as a PostCSS plugin".
- **Soluția adoptată**: `->viteTheme(...)` eliminat, Filament își încarcă tema default din `vendor/filament/filament/dist/theme.css` (compilată, înregistrată ca `Theme::make('app', ...)` în `FilamentServiceProvider`); fonturile StayOps + override-urile sidebar navy/orange se injectează prin `->renderHook(PanelsRenderHook::BODY_START, fn () => '<style>...</style>')` în `AdminPanelProvider` (CSS inline cu `!important` pe clasele `.fi-sidebar*`); `resources/css/filament/admin/theme.css` șters, `vite.config.js` revenit la 2 inputuri, `@tailwindcss/vite` dezinstalat, `npm run build` verde (58 kB CSS + 45 kB JS).
- Widget-urile Filament se randează **lazy** (placeholder `fi-loading-section` + `x-intersect.__lazyLoad`) → testele nu pot asserta textul KPI în HTML-ul inițial; testele assertează markup-ul static (meniu grupat, heading, prezența style-ului `#0f172a` + font Bunny).

**Teste noi (3):** `AdminDashboardCommandCenterTest` — SuperAdmin vede „Command Center"/subheading/stiluri, meniu grupat (Marketplace/Operațional/Financiar/Configurare/Abonamente), Host → 403 pe `/admin`.

**Gate-uri (2026-10-19):** 245/245 PASS (636 assertions), Pint PASS, PHPStan level 6 → 0 erori, `npm run build` PASS, `DemoDataSeeder` re-rulează idempotent (13 rezervări, 10 taskuri).

## Sesiunea 9 — Modulul de administrare a conturilor (Faza 1)

**Audit inițial (constatări):** 4 roluri (SuperAdmin/Host/Provider/Guest) + workers (TeamMember, fără rol); nu exista resursă „Utilizatori" în `/admin`; **zero atribuiri de roluri în tot app-ul** (doar seedere) → Host/Provider nu puteau fi promovați din UI; `/register` crea user **fără niciun rol** (redirect pe dashboard placeholder); nu exista suspendare de cont (tabela `users` fără status); form Gazde/Firme doar link către un user inexistent de creat nicăieri. (Mojibake-ul din „output PowerShell" pe diacritice e doar artifact de consolă — fișierele sunt curate.)

**Decizii (cu utilizatorul):** înregistrare = doar Guest (upgrade Host/Provider mai târziu, în Faza 2); resursa Utilizatori = acțiuni complete; Faza 2 = mesagerie + notificări + profil per tip cont.

**Implementare:**
- Migrare `2026_10_19_000100_add_status_to_users_table`: `status` (default `active`, index), `suspended_at`, `suspended_reason`.
- `User`: `STATUS_ACTIVE/SUSPENDED`, `isSuspended()/suspend($reason)/activate()` (forceFill), `suspended_at` cast datetime, `email_verified_at` adăugat în `#[Fillable]` (formular Filament).
- **`EnsureAccountActive`** middleware (web group append + `authMiddleware` ambele panele): cont suspendat → `Auth::logout()`, sesiune invalidată, redirect `/login` cu `errors.email` („Contul tău a fost suspendat. Motiv: …"); **skip pe sesiunea de impersonare** (`ImpersonationService::SESSION_KEY`) ca SuperAdmin-ul să poată investiga conturi suspendate. Fiindcă **panourile Filament NU rulează în grupul `web`** (au stack propriu de middleware — vezi `vendor/filament/filament/routes/web.php` → `->middleware($panel->getMiddleware())`), middleware-ul a trebuit adăugat explicit în `authMiddleware`-ul ambilor panele.
- `RegisteredUserController`: `assignRole(Role::findOrCreate('Guest', 'web'))` (self-healing, nu depinde de seeder).
- **Hook automat de roluri**: `Host::booted()->saved` → `assignRole(Role::findOrCreate('Host','web'))`, idem `Provider` → `Provider`; orice cale de creare (seeder, Filament, factory) aliniază rolul. `Role::findOrCreate` = fără excepții când tabelele de permisiuni există dar rolurile nu sunt seeduite.
- **Resursa Utilizatori** (`app/Filament/Resources/Users/`, grup navigație nou **„Conturi"**, sort 1, icon Users):
  - `UsersTable`: nume, email (copyable), badge-uri roluri (culori: SuperAdmin danger/Host info/Provider success/Guest gray), status badge, iconițe „Verificat"/„2FA", ultima autentificare (placeholder „Niciodată"); filtre: status, rol (relationship), toggle „Email verificat"; acțiuni: View, Intră în cont, Suspendă, Reactivează, Reset parolă, Edit, Delete (ascunsă după gardă).
  - `UserForm`: Secțiuni Identitate (nume/email unique/telefon/limbă), Securitate (parolă — required doar la create, `dehydrated` doar cât e filled, hash via cast `hashed`; „Email verificat la"), Roluri (`Select->relationship('roles','name')->multiple()->preload()` — **valorile sunt ID-uri de rol, nu nume**).
  - `UserInfolist` (ViewUser): identitate, securitate & acces (status, 2FA badge, motiv/dată suspendare, ultimul login/IP), roluri badge, profil platformă (KYC gazdă/firmă, Stripe, firmă activă Da/Nu via `->state()` — `TextEntry::boolean()` nu există în v5).
  - Acțiuni custom în `app/Filament/Actions/`: `SuspendUserAction` (form cu motiv, invizibil pt. self/SuperAdmin/already suspended), `ActivateUserAction`, `ImpersonateUserAction` (user → `ImpersonationService`, redirect `impersonation.landing`), `SendPasswordResetAction` (`Password::sendResetLink` — notificare directă `ResetPassword` către user, **nu** on-demand → `Notification::assertSentTo`).
  - `UserResource::deletionBlocker()`: interzice ștergerea propriului cont, a unui SuperAdmin și a unui cont cu profil Gazdă/Firmă (FK `cascadeOnDelete` ar șterge lanțul proprietăți/rezervări); folosit în `visible()` pe DeleteAction din tabel + header EditUsers (closure cu `instanceof` narrowing pentru PHPStan).
  - `ListUsers` header: `CreateAction` vizibil după `users.create`.
- Navigație: `navigationGroups(['Conturi','Marketplace','Operațional','Financiar','Configurare'])` în `AdminPanelProvider`.

**Filament v5 — detalii de API întâlnite:** `TextInput::lowercase()` eliminat (nu mai există); `callTableAction('name', $record, data: [...])` named param funcționează; `assertTableActionHidden` disponibil; `Select->relationship()` = valori pe cheia primară a rolului.

**Teste noi (11):** `UserAdministrationTest` — navigație „Conturi/Utilizatori"; creare user cu rol (ID rol) + hash parolă; suspendare → status + logout forțat la următoarea cerere; login + logout imediat pentru cont suspendat; reactivare; `assertTableActionHidden('suspend', SuperAdmin)`; reset link trimis (`assertSentTo`); pagină View; creare Host → rol Host; creare Provider → rol Provider; gardă ștergere (4 motive). `RegistrationTest` actualizat: user nou → `hasRole('Guest')`.

**Gate-uri (2026-10-19):** 256/256 PASS (682 assertions), Pint PASS, PHPStan level 6 → 0 erori, `DemoDataSeeder` re-rulează idempotent, migrare `add_status_to_users` aplicată pe DB-ul dev.

## Sesiunea 10 — Mesageria internă + notificări in-app (Faza 2, partea 1)

**Mesagerie internă (direct + context rezervare):**
- Migrație `2026_10_19_000300_create_conversations_tables`: `conversations` (`subject`, `direct_key` unic, `booking_id`, `task_id`, `last_message_at`), pivot `conversation_user` (`last_read_at`), `messages` (`sender_id`, `body`).
- `Conversation::findOrCreateDirect(User, User)` — cheie `direct_key` = ID-uri sortate (idempotent; reluarea unei conversații nu duplică). `sendMessage()`, `isParticipant()`, `markReadFor()`, `unreadCountFor()` (pivot `last_read_at` vs `messages.created_at`), `othersExcept()`, `titleFor()`.
- `Message::scopeChronological()`; relații `User::{conversations(), messages(), unreadMessagesCount()}`.
- `MessagingController::startWith()` (`POST /messages/with/{user}`) — refuză self și cont suspendat (`403`); opțional `?booking=BK-XXX` atașează rezervarea **doar** dacă ambii utilizatori sunt participanți (guest/host) la acea rezervare, altfel ignoră.
- Rute: `Volt::route('/messages', 'messages.index')`, `Volt::route('/messages/{conversation}', 'messages.show')` (grup `['auth','verified']`).
- Pagini Volt: `messages.index` (listă conversații + badge unread + ultimul mesaj, layout public) și `messages.show` (bule, polling 15s + `markRead`, trimitere cu validare `required|max:2000`).
- Legătură din booking: `bookings.show` afișează link „Contactează gazda" (`contact-link`) → `/messages/with/{host_user}`; nav-ul arată „Mesaje" + badge în `layouts/navigation.blade.php` și `components/layouts/public.blade.php`.

**Notificări in-app (database channel):**
- Migrație `2026_10_19_000200_create_notifications_table` (tabela standard Laravel).
- `NewMessageNotification` (titlu + preview 100 car. + URL conversație), `BookingConfirmedNotification` / `BookingCancelledNotification` (url `bookings.show`) — toate prin canal `database`.
- `App\Livewire\NotificationBell` (componentă Livewire clasică) + `livewire/notification-bell.blade.php`: dropdown, badge unread, `markRead()` (marchează citit + redirect **doar** către URL intern — blochează `//` și originile externe → fallback `/dashboard`), `markAllRead()`, polling 30s.
- `BookingService::confirm()`/`cancel()` notifică **guest + host** in-app (pe lângă emailurile deja existente).
- Panourile Filament `admin` + `provider`: `->databaseNotifications()->databaseNotificationsPolling('30s')` + item de navigație „Mesaje".

**Teste noi (18):** `MessagingTest` — auth redirect, conversație directă creată/reutilizată, self/suspendat interzis, non-participant 403, trimitere mesaj + notificare destinatar (nu expeditor), mesaj gol respins, unread/markRead, listă conversații, notificări confirmare/anulare rezervare guest+host, context booking atașat, badge nav pe dashboard, link contact pe pagina rezervării, notification bell (markRead + redirect, markAllRead, respingere URL extern).

**Gate-uri (2026-10-19):** 274/274 PASS (729 assertions), Pint PASS, PHPStan level 6 → 0 erori.

## Sesiunea 11 — Profil completat (badge profesional) + dashboard per tip cont (Faza 2, partea 2)

**Profil completat cu avatar:**
- `User::avatarUrl()` (discul `public`), `User::initials()` (fallback avatar), `User::profileCompletionSteps()` (cei 6 pași, refolosiți de badge).
- `ProfileUpdateRequest`: reguli noi `phone` (nullable, max 30), `locale` (nullable, `in:ro,en`), `avatar` (imagine jpg/jpeg/png/webp ≤2MB), `remove_avatar` (boolean).
- `ProfileController::update()` — salvează `phone`/`locale` (locale blank ignorat), încarcă avatarul (`store('avatars','public')`), șterge fișierul vechi la înlocuire/`remove_avatar`, invalidează `email_verified_at` doar când emailul se schimbă (comportament păstrat).
- Pagina `/profile`: card „Profil completat" (procent + bară + checklist cu cei 6 pași, `data-testid="profile-completion"`), formular extins (avatar + preview/iniţiale, nume, email, telefon, selector limbă).

**Dashboard per tip cont (`/dashboard` → Volt):**
- Ruta `/dashboard` a trecut de la `view('dashboard')` (placeholder șters) la `Volt::route('/dashboard','dashboard')`.
- `livewire/dashboard.blade.php` (Guest): salut personalizat, badge mesaje necitite + link „Panoul meu" (role-aware prin `RoleRedirector`), hint de completare profil, listă rezervări viitoare (status badge), istoric recent. Host/Provider sunt redirecționați de `RoleRedirector` către `/host`/`/provider` (dashboard-urile lor există deja).

**Teste noi (11):** `ProfileTest` (+5): phone+locale, locale invalid respins, upload+ștergere avatar (Storage fake), `profileCompletion()` reflectă datele, badge pe pagină. `GuestDashboardTest` (6): auth, welcome + empty state, rezervări viitoare, istoric, izolare (nu vede rezervările altui oaspete), hint completare profil.

**Gate-uri (2026-10-19):** **285/285 PASS (769 assertions)**, Pint PASS, PHPStan level 6 → 0 erori. Notă: `ApplicationFeeTest::test_fee_is_zero_without_a_plan_percentage` a eșuat o dată în rulare completă dar trece izolat și la re-rulare completă — flake de ordonare pre-existent (nu atinge plăți/planuri modificate aici).

## Sesiunea 12 — Widget-uri noi Command Center + pagini de rapoarte + Mesaje în admin

**Widget-uri noi (dashboard SuperAdmin):**
- `PlatformHealthService` — scor global 0–100 din 4 factori (dispute deschise max 30, sarcini restante max 25, rezervări afectate max 25, erori aplicație 24h max 20). `report()` → `{score,status,color,label,factors}`; `errorRate()` (cache 5 min), `countApplicationErrors()` (`protected`, citește `storage/logs/laravel.log`). Praguri: ≥90 sănătos, ≥70 atenție, else critic.
- `PlatformHealthWidget` (+ view + partial factor); `KpiOverviewWidget` rescris (10 KPI: MRR, ARR, ARPU, abonamente active, gazde active, companii mentenanță, proprietăți active, dispute deschise, rezervări lună, task-uri mentenanță); `PropertiesByCityWidget` (top 8 orașe); `NeedsAttentionWidget` extins la 8 item-uri (dispute noi/investigație, intervenții urgente, task-uri întârziate, abonamente care expiră 14z, proprietăți blocate, gazde/firme fără KYC); `TopHostsWidget`; `TopProvidersWidget`; `MarketplaceConversionWidget` (funnel). `SubscriptionMetrics::arpu()` adăugat; `DisputeFactory` nou.

**Reorganizare rapoarte (pagini separate în meniu):**
- `Dashboard` păstrează doar KPI + Health + NeedsAttention (grup implicit, „Command Center").
- Pagini noi `FinancialReport` (`/admin/reports/financial`), `OperationalReport` (`/admin/reports/operational`), `MarketplaceReport` (`/admin/reports/marketplace`) — extind `Filament\Pages\Page` (NU `Dashboard`, altfel revendicau ruta `/` și rupeau `filament.admin.pages.dashboard`), randează widget-uri prin `content(Schema)` + `getWidgetsSchemaComponents()`, grup de navigare „Rapoarte", sort 20/30/40.

**Mesaje în admin (Filament nativ, cu sidebar):**
- `Messages` (`/admin/messages`) + view; `ViewConversation` (`messages/{conversation}`, fără item de navigare, mount cu route binding + `markReadFor`, metoda `send()`). Înlocuiește vechiul `NavigationItem` care pierdea sidebar-ul.

**AdminPanelProvider:** adăugat grup „Rapoarte"; corectat mojibake „Operațional".

**Teste:** `PlatformHealthWidgetsTest` (11) — include teste deterministe pe `PlatformHealthService` (curăță dispute/task-uri/rezervări înainte de a aserta scorul, mock `countApplicationErrors`) și `Livewire::test` per widget. `AdminDashboardCommandCenterTest` actualizat: navigare grupată (inclusiv „Rapoarte"), deschidere pagini rapoarte (`/admin/reports/*`) și pagină Mesaje cu `fi-sidebar`.

**Gate-uri (2026-10-20):** **297/298 PASS (809 assertions)**, Pint PASS, PHPStan level 6 → 0 erori. Singurul eșec = flake-ul pre-existent `ApplicationFeeTest::test_fee_is_zero_without_a_plan_percentage` (trece izolat).

## Următorii pași

1. ~~Actualizează `Plan dezvoltare.md`~~ ✅ rămas (tabel de stadiu adăugat; marcat aici).
2. Organizații/tenants (dacă rămâne în plan): model propriu sau Filament Teams pe Spatie Permission (`team` = null momentan).
3. Verifică panoul Host vs Filament — **decizie: păstrăm Livewire/Volt** pentru panoul Host.
4. ~~Rulează gate-urile~~ ✅ rulate 2026-10-19: 256/256 + Pint + PHPStan 0 erori; după sesiunea 10: **274/274**; după sesiunea 11: **285/285 PASS (769 assertions)** + Pint + PHPStan level 6 → 0 erori.
5. ~~Modulul **SaaS**~~ ✅ făcut în sesiunea 7 (abonamente forțate, `application_fee`, webhook, widget MRR/churn).
6. ~~Test dedicat pentru pagina `Provider\Pages\Invoicing`~~ ✅ făcut în sesiunea 6 (+ fix filtru).
7. Commit sesiunile 1-11 (totul ne-commit-uit: rebranding StayOps, landing, demo, Command Center, administrare conturi, mesagerie + notificări, profil + dashboard + changes anterioare: Provider panel, Teams, impersonation, KYC, SaaS/Cashier, teste).
8. **Faza 2 (aprobată)**: ~~mesagerie internă + notificări in-app~~ ✅ sesiunea 10; ~~profil completat (badge profesional) + dashboard per tip cont~~ ✅ sesiunea 11. **Rămas**: dashboard-uri dedicate Host/Provider mai bogate (KPI, grafice) — opțional; Twilio SMS, Mailgun/Postmark, deploy producție (Etapa 5).
