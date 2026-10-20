<?php

namespace App\Filament\Widgets;

use App\Models\Provider;
use App\Models\Task;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TopProvidersWidget extends Widget
{
    protected static ?int $sort = -48;

    protected int|string|array $columnSpan = 3;

    protected string $view = 'filament.widgets.top-providers';

    /**
     * @return array{providers: list<array{name: string, completed: int, rating: float}>, maxCompleted: int}
     */
    protected function getViewData(): array
    {
        /** @var Collection<int, object{provider_id: int, completed: int}> $rows */
        $rows = Task::query()
            ->whereNotNull('provider_id')
            ->whereIn('status', ['completed', 'verified'])
            ->groupBy('provider_id')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(5)
            ->get([
                'provider_id',
                DB::raw('COUNT(*) as completed'),
            ]);

        $providersById = Provider::query()
            ->whereIn('id', $rows->pluck('provider_id'))
            ->get()
            ->keyBy('id');

        $providers = [];

        foreach ($rows as $row) {
            $provider = $providersById->get($row->provider_id);

            $providers[] = [
                'name' => $provider !== null ? ($provider->company_name ?: 'Companie #'.$row->provider_id) : 'Companie #'.$row->provider_id,
                'completed' => (int) $row->completed,
                'rating' => $provider !== null ? (float) $provider->rating_avg : 0.0,
            ];
        }

        return [
            'providers' => $providers,
            'maxCompleted' => max(1, (int) ($rows->max('completed') ?? 0)),
        ];
    }
}
