<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LiveActivityWidget;
use App\Filament\Widgets\OperationalFlowWidget;
use App\Filament\Widgets\PropertiesByCityWidget;
use App\Filament\Widgets\TopProvidersWidget;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class OperationalReport extends Page
{
    protected static ?string $title = 'Raport operațional';

    protected static ?string $navigationLabel = 'Operațional';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Rapoarte';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'reports/operational';

    public function getSubheading(): string|Htmlable|null
    {
        return 'Fluxul de lucru, acoperirea pe orașe și firmele de mentenanță.';
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            OperationalFlowWidget::class,
            PropertiesByCityWidget::class,
            TopProvidersWidget::class,
            LiveActivityWidget::class,
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
