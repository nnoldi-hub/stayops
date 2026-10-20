<?php

namespace App\Filament\Widgets;

use App\Services\PlatformHealthService;
use Filament\Widgets\Widget;

class PlatformHealthWidget extends Widget
{
    protected static ?int $sort = -95;

    protected int|string|array $columnSpan = 6;

    protected string $view = 'filament.widgets.platform-health';

    /**
     * @return array{score: int, status: string, color: string, label: string, factors: list<array{label: string, value: int, points: int, max: int, url: string|null}>}
     */
    protected function getViewData(): array
    {
        return app(PlatformHealthService::class)->report();
    }
}
