<?php

namespace App\Filament\Resources\Providers\Tables;

use App\Filament\Actions\ApproveKycAction;
use App\Filament\Actions\ImpersonateAction;
use App\Filament\Actions\RejectKycAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('company_name')
                    ->searchable(),
                TextColumn::make('cui')
                    ->searchable(),
                TextColumn::make('service_types')
                    ->label('Servicii')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', $state) : (string) $state),
                TextColumn::make('kyc_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'submitted' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Activ')
                    ->boolean(),
                TextColumn::make('rating_avg')
                    ->label('Rating')
                    ->numeric(2),
                TextColumn::make('jobs_completed')
                    ->label('Lucrări'),
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
            ])
            ->filters([
                SelectFilter::make('kyc_status')
                    ->label('Status KYC')
                    ->options([
                        'pending' => 'În așteptare',
                        'submitted' => 'Trimis spre verificare',
                        'approved' => 'Aprobat',
                        'rejected' => 'Respins',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ApproveKycAction::make('providers.approve_kyc'),
                RejectKycAction::make('providers.approve_kyc'),
                ImpersonateAction::make(),
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
