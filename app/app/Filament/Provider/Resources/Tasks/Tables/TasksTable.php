<?php

namespace App\Filament\Provider\Resources\Tasks\Tables;

use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('scheduled_at', 'asc')
            ->columns([
                TextColumn::make('title')
                    ->label('Sarcină')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.title')
                    ->label('Proprietate')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tip')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cleaning' => 'Curățenie',
                        'repair' => 'Reparație',
                        'inspection' => 'Inspecție',
                        'laundry' => 'Spălătorie',
                        'restock' => 'Aprovizionare',
                        default => 'Altele',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'gray',
                        'assigned' => 'info',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'verified' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Deschisă',
                        'assigned' => 'Atribuită',
                        'in_progress' => 'În lucru',
                        'completed' => 'Finalizată',
                        'verified' => 'Verificată',
                        'cancelled' => 'Anulată',
                        default => $state,
                    }),
                TextColumn::make('priority')
                    ->label('Prioritate')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'normal' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('scheduled_at')
                    ->label('Programată')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('assignee.name')
                    ->label('Atribuit')
                    ->placeholder('—'),
                TextColumn::make('actual_cost')
                    ->label('Cost real')
                    ->money('RON')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Deschisă',
                        'assigned' => 'Atribuită',
                        'in_progress' => 'În lucru',
                        'completed' => 'Finalizată',
                        'verified' => 'Verificată',
                        'cancelled' => 'Anulată',
                    ]),
                SelectFilter::make('type')
                    ->label('Tip')
                    ->options([
                        'cleaning' => 'Curățenie',
                        'repair' => 'Reparație',
                        'inspection' => 'Inspecție',
                        'laundry' => 'Spălătorie',
                        'restock' => 'Aprovizionare',
                        'other' => 'Altele',
                    ]),
                SelectFilter::make('priority')
                    ->label('Prioritate')
                    ->options([
                        'low' => 'Scăzută',
                        'normal' => 'Normală',
                        'high' => 'Ridicată',
                        'urgent' => 'Urgentă',
                    ]),
            ])
            ->recordActions([
                Action::make('start')
                    ->label('Începe')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Task $record): bool => in_array($record->status, ['open', 'assigned'], true))
                    ->action(function (Task $record): void {
                        $record->update([
                            'status' => 'in_progress',
                            'started_at' => $record->started_at ?? now(),
                        ]);
                    }),
                Action::make('complete')
                    ->label('Finalizează')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Task $record): bool => $record->status === 'in_progress')
                    ->action(function (Task $record): void {
                        if (! $record->hasAfterPhotos()) {
                            Notification::make()
                                ->title('Dovada muncii lipsește')
                                ->body('Încarcă cel puțin o poză „după" (editează sarcina) înainte de a o finaliza.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
