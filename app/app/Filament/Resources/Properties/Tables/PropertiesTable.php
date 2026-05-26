<?php

namespace App\Filament\Resources\Properties\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PropertiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('host.id')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('property_type')
                    ->searchable(),
                TextColumn::make('address')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('county')
                    ->searchable(),
                TextColumn::make('country')
                    ->searchable(),
                TextColumn::make('postal_code')
                    ->searchable(),
                TextColumn::make('base_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('cleaning_fee')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_guests')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bedrooms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('beds')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bathrooms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('check_in_time')
                    ->time()
                    ->sortable(),
                TextColumn::make('check_out_time')
                    ->time()
                    ->sortable(),
                TextColumn::make('min_nights')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_nights')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('rating_avg')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reviews_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
