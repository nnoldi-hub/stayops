<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Task;
use Filament\Widgets\Widget;

class OperationalFlowWidget extends Widget
{
    protected static ?int $sort = -65;

    protected int|string|array $columnSpan = 3;

    protected string $view = 'filament.widgets.operational-flow';

    /**
     * @return array{steps: list<array{label: string, value: int, icon: string}>}
     */
    protected function getViewData(): array
    {
        return [
            'steps' => [
                [
                    'label' => 'Rezervări active',
                    'value' => Booking::query()
                        ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                        ->count(),
                    'icon' => 'heroicon-o-calendar-days',
                ],
                [
                    'label' => 'Check-out azi',
                    'value' => Booking::query()
                        ->whereDate('check_out', today())
                        ->whereIn('status', ['confirmed', 'checked_in'])
                        ->count(),
                    'icon' => 'heroicon-o-arrow-right-on-rectangle',
                ],
                [
                    'label' => 'Task-uri create azi',
                    'value' => Task::query()->whereDate('created_at', today())->count(),
                    'icon' => 'heroicon-o-clipboard-document-check',
                ],
                [
                    'label' => 'Task-uri finalizate azi',
                    'value' => Task::query()->whereDate('completed_at', today())->count(),
                    'icon' => 'heroicon-o-check-badge',
                ],
                [
                    'label' => 'Facturi generate (luna)',
                    'value' => Booking::query()
                        ->where('payment_status', 'paid')
                        ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                        ->count(),
                    'icon' => 'heroicon-o-document-currency-dollar',
                ],
            ],
        ];
    }
}
