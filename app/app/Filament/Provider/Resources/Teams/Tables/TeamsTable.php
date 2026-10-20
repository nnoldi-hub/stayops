<?php

namespace App\Filament\Provider\Resources\Teams\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Echipă')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('members_count')
                    ->label('Membri')
                    ->counts('members')
                    ->badge(),
                TextColumn::make('description')
                    ->label('Descriere')
                    ->limit(60)
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Creată')
                    ->dateTime('d.m.Y')
                    ->sortable(),
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
