<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\KpiOverviewWidget;
use App\Filament\Widgets\NeedsAttentionWidget;
use App\Filament\Widgets\PlatformHealthWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Command Center';

    protected static ?string $navigationLabel = 'Command Center';

    public function getHeading(): string|Htmlable
    {
        $name = auth()->user()?->name;

        return 'Bun venit, '.($name ?? 'admin').' 👋';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Iată starea platformei StayOps astăzi — '.now()->translatedFormat('d F Y');
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            KpiOverviewWidget::class,
            PlatformHealthWidget::class,
            NeedsAttentionWidget::class,
        ];
    }

    public function getColumns(): int
    {
        return 6;
    }
}
