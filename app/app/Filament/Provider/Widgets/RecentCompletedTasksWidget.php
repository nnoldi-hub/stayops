<?php

namespace App\Filament\Provider\Widgets;

use App\Models\Task;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentCompletedTasksWidget extends TableWidget
{
    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Istoric recent (finalizate)')
            ->query(
                Task::query()
                    ->visibleToProviderUser(auth()->id())
                    ->whereIn('status', ['completed', 'verified', 'cancelled'])
                    ->latest('updated_at'),
            )
            ->columns([
                TextColumn::make('title')
                    ->label('Sarcină')
                    ->searchable(),
                TextColumn::make('property.title')
                    ->label('Proprietate'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed', 'verified' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Finalizată',
                        'verified' => 'Verificată',
                        'cancelled' => 'Anulată',
                        default => $state,
                    }),
                TextColumn::make('completed_at')
                    ->label('Finalizată la')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('actual_cost')
                    ->label('Cost real')
                    ->money('RON')
                    ->placeholder('—'),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
