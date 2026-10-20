<?php

namespace App\Filament\Widgets;

use App\Models\Dispute;
use App\Models\Host;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Task;
use Filament\Widgets\Widget;
use Laravel\Cashier\Subscription;

class NeedsAttentionWidget extends Widget
{
    protected static ?int $sort = -90;

    protected int|string|array $columnSpan = 6;

    protected string $view = 'filament.widgets.needs-attention';

    /**
     * @return array{items: list<array{label: string, count: int, url: string, color: string}>, total: int}
     */
    protected function getViewData(): array
    {
        $items = [
            [
                'label' => 'Dispute noi',
                'count' => Dispute::query()->where('status', 'open')->count(),
                'url' => '/admin/disputes',
                'color' => 'danger',
            ],
            [
                'label' => 'Dispute în investigație',
                'count' => Dispute::query()
                    ->whereIn('status', ['investigating', 'awaiting_response', 'escalated'])
                    ->count(),
                'url' => '/admin/disputes',
                'color' => 'danger',
            ],
            [
                'label' => 'Intervenții urgente',
                'count' => Task::query()
                    ->where('priority', 'urgent')
                    ->whereIn('status', ['open', 'assigned', 'in_progress'])
                    ->count(),
                'url' => '/admin/tasks',
                'color' => 'danger',
            ],
            [
                'label' => 'Task-uri întârziate',
                'count' => Task::query()
                    ->whereIn('status', ['open', 'assigned', 'in_progress'])
                    ->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '<', now())
                    ->count(),
                'url' => '/admin/tasks',
                'color' => 'warning',
            ],
            [
                'label' => 'Abonamente care expiră (14 zile)',
                'count' => Subscription::query()
                    ->where('type', 'host')
                    ->whereNotNull('ends_at')
                    ->whereBetween('ends_at', [now(), now()->addDays(14)])
                    ->count(),
                'url' => '/admin/plans',
                'color' => 'warning',
            ],
            [
                'label' => 'Proprietăți blocate (în pauză)',
                'count' => Property::query()->where('status', 'paused')->count(),
                'url' => '/admin/properties',
                'color' => 'warning',
            ],
            [
                'label' => 'Gazde fără KYC aprobat',
                'count' => Host::query()->where('kyc_status', '!=', 'approved')->count(),
                'url' => '/admin/hosts',
                'color' => 'warning',
            ],
            [
                'label' => 'Firme fără KYC aprobat',
                'count' => Provider::query()->where('kyc_status', '!=', 'approved')->count(),
                'url' => '/admin/providers',
                'color' => 'warning',
            ],
        ];

        return [
            'items' => $items,
            'total' => (int) array_sum(array_column($items, 'count')),
        ];
    }
}
