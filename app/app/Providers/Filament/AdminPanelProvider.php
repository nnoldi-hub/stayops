<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('StayOps')
            ->colors([
                'primary' => Color::Orange,
            ])
            ->navigationGroups([
                'Conturi',
                'Marketplace',
                'Operațional',
                'Financiar',
                'Rapoarte',
                'Configurare',
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => '<style>'.$this->stayOpsStyles().'</style>',
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureAccountActive::class,
                EnsureTwoFactorAuthenticated::class,
            ]);
    }

    /**
     * Aplicația folosește Tailwind v3 (PostCSS), iar Filament v5 folosește Tailwind v4,
     * deci un al doilea build CSS în același manifest ar intra în conflict cu tema
     * lui Filament ( deja compilată în `dist/theme.css` ). Stilurile StayOps se
     * injectează direct, peste tema default.
     */
    private function stayOpsStyles(): string
    {
        return <<<'CSS'
@import url('https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:500,600,700,800&display=swap');

.fi-body,
.fi-main {
    --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
}

.fi-body {
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
}

/* StayOps Command Center — sidebar navy + accent portocaliu */

.fi-sidebar {
    background-color: #0f172a !important;
}

.fi-sidebar .fi-sidebar-item-label {
    color: #cbd5e1 !important;
}

.fi-sidebar .fi-sidebar-item-btn > .fi-icon {
    color: #94a3b8 !important;
}

.fi-sidebar .fi-sidebar-item-btn:hover,
.fi-sidebar .fi-sidebar-item-btn:focus-visible {
    background-color: #1e293b !important;
}

.fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
    background-color: rgba(249, 115, 22, 0.18) !important;
}

.fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon,
.fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-sidebar-item-label {
    color: #fb923c !important;
}

.fi-sidebar .fi-sidebar-group-label {
    color: #64748b !important;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-size: 0.7rem;
}

.fi-sidebar .fi-sidebar-group-btn .fi-icon {
    color: #64748b !important;
}

.fi-sidebar .fi-sidebar-footer {
    border-color: rgba(255, 255, 255, 0.08) !important;
}

.fi-sidebar-header .fi-logo {
    color: #ffffff !important;
}

.fi-body-has-topbar .fi-sidebar-header {
    background-color: #0f172a !important;
    box-shadow: none !important;
    ring: 0 !important;
}
CSS;
    }
}
