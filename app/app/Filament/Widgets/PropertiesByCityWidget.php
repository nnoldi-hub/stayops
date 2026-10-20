<?php

namespace App\Filament\Widgets;

use App\Models\Property;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class PropertiesByCityWidget extends Widget
{
    protected static ?int $sort = -55;

    protected int|string|array $columnSpan = 3;

    protected string $view = 'filament.widgets.properties-by-city';

    /**
     * @return array{cities: list<array{city: string, total: int, published: int, percent: int}>, max: int, totalCities: int}
     */
    protected function getViewData(): array
    {
        $rows = DB::table('properties')
            ->select('city')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published")
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->groupBy('city')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(8)
            ->get()
            ->map(fn (object $row): array => [
                'city' => (string) $row->city,
                'total' => (int) $row->total,
                'published' => (int) $row->published,
            ]);

        $max = (int) $rows->max('total');
        $totalCities = Property::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->count('city');

        $cities = $rows->map(fn (array $row): array => [
            ...$row,
            'percent' => $max > 0 ? (int) round($row['total'] / $max * 100) : 0,
        ])->values()->all();

        return [
            'cities' => $cities,
            'max' => $max,
            'totalCities' => $totalCities,
        ];
    }
}
