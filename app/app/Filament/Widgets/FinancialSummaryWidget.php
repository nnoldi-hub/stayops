<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Task;
use App\Services\SubscriptionMetrics;
use Filament\Widgets\Widget;

class FinancialSummaryWidget extends Widget
{
    protected static ?int $sort = -70;

    protected int|string|array $columnSpan = 3;

    protected string $view = 'filament.widgets.financial-summary';

    /**
     * @return array{rows: list<array{label: string, value: string, icon: string}>, total: string}
     */
    protected function getViewData(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $saas = app(SubscriptionMetrics::class)->mrr();

        $bookingFees = (float) Booking::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('platform_fee');

        $maintenance = (float) Task::query()
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->sum('actual_cost');

        return [
            'rows' => [
                ['label' => 'Venit SaaS (abonamente)', 'value' => number_format($saas, 0).' RON', 'icon' => 'heroicon-o-banknotes'],
                ['label' => 'Comisioane rezervări', 'value' => number_format($bookingFees, 0).' RON', 'icon' => 'heroicon-o-credit-card'],
                ['label' => 'Comisioane mentenanță', 'value' => number_format($maintenance, 0).' RON', 'icon' => 'heroicon-o-wrench-screwdriver'],
            ],
            'total' => number_format($saas + $bookingFees + $maintenance, 0).' RON',
        ];
    }
}
