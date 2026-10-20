<?php

namespace App\Filament\Provider\Widgets;

use App\Models\Task;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TaskStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -10;

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $pending = Task::query()->visibleToProviderUser($user->id)
            ->whereIn('status', ['open', 'assigned'])
            ->count();

        $inProgress = Task::query()->visibleToProviderUser($user->id)
            ->where('status', 'in_progress')
            ->count();

        $completed = Task::query()->visibleToProviderUser($user->id)
            ->whereIn('status', ['completed', 'verified'])
            ->count();

        return [
            Stat::make('De început', $pending)
                ->description('Deschise sau atribuite')
                ->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->color('warning'),
            Stat::make('În lucru', $inProgress)
                ->description('În execuție')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('info'),
            Stat::make('Finalizate', $completed)
                ->description('Finalizate / verificate')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
