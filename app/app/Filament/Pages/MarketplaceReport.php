<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MarketplaceConversionWidget;
use App\Filament\Widgets\MarketplaceOverviewWidget;
use App\Filament\Widgets\TopHostsWidget;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class MarketplaceReport extends Page
{
    protected static ?string $title = 'Raport marketplace';

    protected static ?string $navigationLabel = 'Marketplace';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string|\UnitEnum|null $navigationGroup = 'Rapoarte';

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'reports/marketplace';

    public function getSubheading(): string|Htmlable|null
    {
        return 'Gazde, proprietăți și conversia către rezervări.';
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            MarketplaceOverviewWidget::class,
            MarketplaceConversionWidget::class,
            TopHostsWidget::class,
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
