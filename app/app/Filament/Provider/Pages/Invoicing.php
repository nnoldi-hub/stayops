<?php

namespace App\Filament\Provider\Pages;

use App\Models\Host;
use App\Models\Property;
use App\Models\Task;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class Invoicing extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Facturare';

    protected static ?string $title = 'Raport de facturare';

    protected static string|\UnitEnum|null $navigationGroup = 'Operațional';

    protected static ?int $navigationSort = 30;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected string $view = 'filament.provider.pages.invoicing';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->visibleToProviderUser(auth()->id())
                    ->whereIn('status', ['completed', 'verified'])
                    ->with('property.host'),
            )
            ->columns([
                TextColumn::make('completed_at')
                    ->label('Finalizată')
                    ->dateTime('d.m.Y')
                    ->sortable(),
                TextColumn::make('property.title')
                    ->label('Proprietate'),
                TextColumn::make('property.host.company_name')
                    ->label('Proprietar')
                    ->placeholder('—'),
                TextColumn::make('title')
                    ->label('Sarcină')
                    ->limit(50),
                TextColumn::make('actual_cost')
                    ->label('Cost')
                    ->money('RON')
                    ->placeholder('—')
                    ->summarize(Sum::make()->money('RON')),
            ])
            ->filters([
                SelectFilter::make('host')
                    ->label('Proprietar')
                    ->options(fn (): array => $this->hostOptions())
                    ->query(fn (Builder $query, array $state): Builder => $query->when(
                        filled($state['value'] ?? null),
                        fn (Builder $q) => $q->whereHas(
                            'property',
                            fn (Builder $relation) => $relation->where('host_id', $state['value']),
                        ),
                    )),
                SelectFilter::make('month')
                    ->label('Luna')
                    ->options(fn (): array => $this->monthOptions())
                    ->query(fn (Builder $query, array $state): Builder => $query->when(
                        filled($state['value'] ?? null),
                        fn (Builder $q) => $q->whereBetween('completed_at', [
                            Carbon::parse($state['value'].'-01'),
                            Carbon::parse($state['value'].'-01')->endOfMonth(),
                        ]),
                    )),
            ])
            ->defaultSort('completed_at', 'desc');
    }

    /**
     * @return array<int, string>
     */
    protected function hostOptions(): array
    {
        return Host::query()
            ->whereIn('id', Property::query()
                ->whereIn('id', Task::query()
                    ->visibleToProviderUser(auth()->id())
                    ->whereIn('status', ['completed', 'verified'])
                    ->pluck('property_id'))
                ->pluck('host_id'))
            ->pluck('company_name', 'id')
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    protected function monthOptions(): array
    {
        return collect(range(0, 11))
            ->mapWithKeys(fn (int $i): array => [
                Carbon::now()->subMonths($i)->format('Y-m') => Carbon::now()->subMonths($i)->format('F Y'),
            ])
            ->toArray();
    }
}
