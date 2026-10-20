<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BookingsChartWidget;
use App\Filament\Widgets\FinancialSummaryWidget;
use App\Filament\Widgets\MrrGrowthChartWidget;
use App\Filament\Widgets\SubscriptionMetricsWidget;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class FinancialReport extends Page
{
    protected static ?string $title = 'Raport financiar';

    protected static ?string $navigationLabel = 'Financiar';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Rapoarte';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'reports/financial';

    public function getSubheading(): string|Htmlable|null
    {
        return 'Venituri recurente, comisioane și rezervări.';
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            FinancialSummaryWidget::class,
            MrrGrowthChartWidget::class,
            BookingsChartWidget::class,
            SubscriptionMetricsWidget::class,
        ];
    }

    public function getColumns(): int
    {
        return 6;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make($this->getColumns())
                ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets())),
        ]);
    }
}
