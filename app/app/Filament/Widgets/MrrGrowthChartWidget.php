<?php

namespace App\Filament\Widgets;

use App\Services\SubscriptionMetrics;
use Filament\Widgets\ChartWidget;

class MrrGrowthChartWidget extends ChartWidget
{
    protected static ?int $sort = -80;

    protected int|string|array $columnSpan = 3;

    protected ?string $heading = 'MRR Growth';

    protected ?string $description = 'Venitul recurent lunar, ultimele 12 luni.';

    protected ?string $pollingInterval = null;

    protected string $color = 'primary';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $series = app(SubscriptionMetrics::class)->mrrSeries(12);

        return [
            'labels' => $series['labels'],
            'datasets' => [
                [
                    'label' => 'MRR (RON)',
                    'data' => $series['values'],
                    'fill' => 'start',
                    'tension' => 0.35,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
