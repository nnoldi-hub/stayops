<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Host;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TopHostsWidget extends Widget
{
    protected static ?int $sort = -50;

    protected int|string|array $columnSpan = 3;

    protected string $view = 'filament.widgets.top-hosts';

    /**
     * @return array{hosts: list<array{name: string, bookings: int, revenue: float}>, maxBookings: int}
     */
    protected function getViewData(): array
    {
        $since = now()->subDays(90);

        /** @var Collection<int, object{host_id: int, bookings: int, revenue: float}> $rows */
        $rows = Booking::query()
            ->join('properties', 'bookings.property_id', '=', 'properties.id')
            ->where('bookings.created_at', '>=', $since)
            ->whereNotNull('properties.host_id')
            ->groupBy('properties.host_id')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(5)
            ->get([
                'properties.host_id',
                DB::raw('COUNT(*) as bookings'),
                DB::raw('SUM(bookings.host_payout) as revenue'),
            ]);

        $hosts = [];
        $hostsById = Host::query()
            ->with('user')
            ->whereIn('id', $rows->pluck('host_id'))
            ->get()
            ->keyBy('id');

        foreach ($rows as $row) {
            $host = $hostsById->get($row->host_id);

            $name = 'Gazdă #'.$row->host_id;

            if ($host !== null) {
                $name = $host->company_name ?: $host->user->name;
            }

            $hosts[] = [
                'name' => $name,
                'bookings' => (int) $row->bookings,
                'revenue' => (float) $row->revenue,
            ];
        }

        return [
            'hosts' => $hosts,
            'maxBookings' => max(1, (int) ($rows->max('bookings') ?? 0)),
        ];
    }
}
