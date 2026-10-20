<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Actions\ActivateUserAction;
use App\Filament\Actions\ImpersonateUserAction;
use App\Filament\Actions\SendPasswordResetAction;
use App\Filament\Actions\SuspendUserAction;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nume')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copiat'),
                TextColumn::make('roles.name')
                    ->label('Roluri')
                    ->badge()
                    ->color(fn (string $state): string => UserResource::roleBadgeColor($state)),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === User::STATUS_SUSPENDED
                        ? 'Suspendat'
                        : 'Activ')
                    ->color(fn (string $state): string => $state === User::STATUS_SUSPENDED
                        ? 'danger'
                        : 'success'),
                IconColumn::make('email_verified_at')
                    ->label('Verificat')
                    ->boolean(),
                IconColumn::make('two_factor_confirmed_at')
                    ->label('2FA')
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->label('Ultima autentificare')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Niciodată'),
                TextColumn::make('created_at')
                    ->label('Creat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        User::STATUS_ACTIVE => 'Activ',
                        User::STATUS_SUSPENDED => 'Suspendat',
                    ]),
                SelectFilter::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name'),
                Filter::make('verified')
                    ->label('Email verificat')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),
            ])
            ->recordActions([
                ViewAction::make(),
                ImpersonateUserAction::make(),
                SuspendUserAction::make(),
                ActivateUserAction::make(),
                SendPasswordResetAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (User $record): bool => auth()->user()?->can('users.delete') === true
                        && UserResource::deletionBlocker($record) === null),
            ])
            ->toolbarActions([]);
    }
}
