<?php

namespace App\Filament\Widgets;

use App\Models\Host;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Team;
use Filament\Widgets\Widget;

class MarketplaceOverviewWidget extends Widget
{
    protected static ?int $sort = -60;

    protected int|string|array $columnSpan = 6;

    protected string $view = 'filament.widgets.marketplace-overview';

    /**
     * @return array{sections: list<array{title: string, icon: string, rows: list<array{label: string, count: int, color: string}>}>}
     */
    protected function getViewData(): array
    {
        $hosts = Host::query();
        $properties = Property::query();
        $providers = Provider::query();

        return [
            'sections' => [
                [
                    'title' => 'Gazde',
                    'icon' => 'heroicon-o-home-modern',
                    'rows' => [
                        ['label' => 'Active (KYC aprobat)', 'count' => (clone $hosts)->where('kyc_status', 'approved')->count(), 'color' => 'success'],
                        ['label' => 'În verificare KYC', 'count' => (clone $hosts)->whereIn('kyc_status', ['pending', 'submitted'])->count(), 'color' => 'warning'],
                        ['label' => 'Respinte', 'count' => (clone $hosts)->where('kyc_status', 'rejected')->count(), 'color' => 'danger'],
                    ],
                ],
                [
                    'title' => 'Proprietăți',
                    'icon' => 'heroicon-o-building-office-2',
                    'rows' => [
                        ['label' => 'Publicate', 'count' => (clone $properties)->where('status', 'published')->count(), 'color' => 'success'],
                        ['label' => 'Draft', 'count' => (clone $properties)->where('status', 'draft')->count(), 'color' => 'gray'],
                        ['label' => 'Puse în pauză', 'count' => (clone $properties)->where('status', 'paused')->count(), 'color' => 'warning'],
                    ],
                ],
                [
                    'title' => 'Firme de mentenanță',
                    'icon' => 'heroicon-o-wrench-screwdriver',
                    'rows' => [
                        ['label' => 'Active (KYC aprobat)', 'count' => (clone $providers)->where('kyc_status', 'approved')->count(), 'color' => 'success'],
                        ['label' => 'În verificare KYC', 'count' => (clone $providers)->whereIn('kyc_status', ['pending', 'submitted'])->count(), 'color' => 'warning'],
                        ['label' => 'Echipe înscrise', 'count' => Team::query()->count(), 'color' => 'info'],
                    ],
                ],
            ],
        ];
    }
}
