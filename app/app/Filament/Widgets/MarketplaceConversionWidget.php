<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Host;
use App\Models\Property;
use Filament\Widgets\Widget;

class MarketplaceConversionWidget extends Widget
{
    protected static ?int $sort = -52;

    protected int|string|array $columnSpan = 6;

    protected string $view = 'filament.widgets.marketplace-conversion';

    /**
     * @return array{stages: list<array{label: string, count: int, percent: int, color: string}>, activationRate: float, bookingRate: float}
     */
    protected function getViewData(): array
    {
        $hostsTotal = Host::query()->count();
        $hostsActive = Host::query()->withActiveSubscription()->count();
        $publishedProperties = Property::query()->publiclyVisible()->count();
        $confirmedBookings = Booking::query()
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->count();

        $top = max(1, $hostsTotal);

        $stages = [
            [
                'label' => 'Gazde înscrise',
                'count' => $hostsTotal,
                'percent' => 100,
                'color' => 'primary',
            ],
            [
                'label' => 'Gazde cu abonament activ',
                'count' => $hostsActive,
                'percent' => (int) round($hostsActive / $top * 100),
                'color' => 'info',
            ],
            [
                'label' => 'Proprietăți publicate',
                'count' => $publishedProperties,
                'percent' => $hostsTotal > 0 ? (int) round($publishedProperties / max(1, $hostsTotal) * 100) : 0,
                'color' => 'success',
            ],
            [
                'label' => 'Rezervări confirmate',
                'count' => $confirmedBookings,
                'percent' => $hostsTotal > 0 ? (int) round($confirmedBookings / max(1, $hostsTotal) * 100) : 0,
                'color' => 'warning',
            ],
        ];

        $activationRate = $hostsTotal > 0 ? round($hostsActive / $hostsTotal * 100, 1) : 0.0;
        $bookingRate = $hostsActive > 0 ? round($confirmedBookings / $hostsActive, 2) : 0.0;

        return [
            'stages' => $stages,
            'activationRate' => $activationRate,
            'bookingRate' => $bookingRate,
        ];
    }
}
