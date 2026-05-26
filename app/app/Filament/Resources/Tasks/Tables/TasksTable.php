<?php

namespace App\Filament\Resources\Tasks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('property.title')
                    ->searchable(),
                TextColumn::make('booking.id')
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('provider.id')
                    ->searchable(),
                TextColumn::make('assigned_to_user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('priority')
                    ->searchable(),
                TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('estimated_cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('actual_cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
