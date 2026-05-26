<?php

namespace App\Filament\Resources\Bookings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->searchable(),
                TextColumn::make('property.title')
                    ->searchable(),
                TextColumn::make('guest.name')
                    ->searchable(),
                TextColumn::make('guest_name')
                    ->searchable(),
                TextColumn::make('guest_email')
                    ->searchable(),
                TextColumn::make('guest_phone')
                    ->searchable(),
                TextColumn::make('check_in')
                    ->date()
                    ->sortable(),
                TextColumn::make('check_out')
                    ->date()
                    ->sortable(),
                TextColumn::make('nights')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('guests_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('adults')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('children')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('nightly_rate')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cleaning_fee')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('platform_fee')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('host_payout')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('source')
                    ->searchable(),
                TextColumn::make('external_id')
                    ->searchable(),
                TextColumn::make('stripe_payment_intent_id')
                    ->searchable(),
                TextColumn::make('payment_status')
                    ->searchable(),
                TextColumn::make('cancelled_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cancellation_reason')
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
