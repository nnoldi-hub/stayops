<?php

namespace App\Filament\Widgets;

use App\Services\SubscriptionMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubscriptionMetricsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -20;

    protected function getStats(): array
    {
        $metrics = app(SubscriptionMetrics::class);
        $churn = $metrics->churn();

        return [
            Stat::make('MRR', number_format($metrics->mrr(), 2).' RON')
                ->description('Venit recurent lunar')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Abonamente active', $metrics->activeCount())
                ->description('Gazde cu abonament valid')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
            Stat::make('Churn (30 zile)', number_format($churn['rate'], 1).'%')
                ->description($churn['canceled'].' abonamente anulate')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($churn['rate'] > 5 ? 'danger' : 'gray'),
        ];
    }
}
