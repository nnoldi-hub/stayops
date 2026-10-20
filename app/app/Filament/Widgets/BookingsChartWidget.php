<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class BookingsChartWidget extends ChartWidget
{
    protected static ?int $sort = -75;

    protected int|string|array $columnSpan = 3;

    protected ?string $heading = 'Rezervări';

    protected ?string $description = 'Rezervări după lună de check-in, ultimele 12 luni.';

    protected ?string $pollingInterval = null;

    protected string $color = 'info';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $start = now()->subMonthsNoOverflow(11)->startOfMonth();

        $bookings = Booking::query()
            ->where('check_in', '>=', $start)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'no_show', 'disputed'])
            ->get(['check_in']);

        $labels = [];
        $values = [];

        foreach (range(11, 0) as $offset) {
            $month = now()->subMonthsNoOverflow($offset)->startOfMonth();

            $labels[] = $month->translatedFormat('M');
            $values[] = $bookings->filter(fn (Booking $booking): bool => Carbon::parse($booking->check_in)->isSameMonth($month))->count();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Rezervări',
                    'data' => $values,
                    'borderRadius' => 6,
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
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
