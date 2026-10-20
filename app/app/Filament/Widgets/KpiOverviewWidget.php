<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Dispute;
use App\Models\Host;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Task;
use App\Services\SubscriptionMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KpiOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -100;

    protected int|string|array $columnSpan = 6;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $metrics = app(SubscriptionMetrics::class);

        $activeSubscriptions = $metrics->activeCount();
        $activeProviders = Provider::query()
            ->where('is_active', true)
            ->where('kyc_status', 'approved')
            ->count();
        $openDisputes = Dispute::query()
            ->whereIn('status', ['open', 'investigating', 'awaiting_response', 'escalated'])
            ->count();

        return [
            Stat::make('MRR', number_format($metrics->mrr(), 0).' RON')
                ->description('Venit recurent lunar')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('ARR', number_format($metrics->arr(), 0).' RON')
                ->description('Proiecție anualizată')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('ARPU', number_format($metrics->arpu(), 0).' RON')
                ->description('Venit mediu / abonament')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),
            Stat::make('Abonamente active', $activeSubscriptions)
                ->description('Gazde cu abonament valid')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('primary'),
            Stat::make('Gazde active', Host::query()->withActiveSubscription()->count())
                ->description('Cu abonament valid')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
            Stat::make('Companii mentenanță', $activeProviders)
                ->description('Active și KYC aprobat')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('primary'),
            Stat::make('Proprietăți active', Property::query()->publiclyVisible()->count())
                ->description('Publicate pe marketplace')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color('primary'),
            Stat::make('Dispute deschise', $openDisputes)
                ->description($openDisputes > 0 ? 'Necesită investigație' : 'Niciuna în lucru')
                ->descriptionIcon($openDisputes > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($openDisputes > 0 ? 'danger' : 'success'),
            Stat::make('Rezervări (luna curentă)', Booking::query()
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count())
                ->description('Toate sursele')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
            Stat::make('Task-uri mentenanță', Task::query()->count())
                ->description(Task::query()
                    ->whereIn('status', ['open', 'assigned', 'in_progress'])
                    ->count().' în lucru')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('warning'),
        ];
    }
}
