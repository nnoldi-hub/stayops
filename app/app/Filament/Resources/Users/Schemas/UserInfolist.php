<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitate')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nume'),
                        TextEntry::make('email')
                            ->label('Email'),
                        TextEntry::make('email_verified_at')
                            ->label('Email verificat la')
                            ->dateTime()
                            ->placeholder('Neverificat'),
                        TextEntry::make('phone')
                            ->label('Telefon')
                            ->placeholder('—'),
                        TextEntry::make('locale')
                            ->label('Limbă')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'en' => 'English',
                                default => 'Română',
                            }),
                        TextEntry::make('created_at')
                            ->label('Cont creat')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Securitate & acces')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => $state === User::STATUS_SUSPENDED
                                ? 'Suspendat'
                                : 'Activ')
                            ->color(fn (string $state): string => $state === User::STATUS_SUSPENDED
                                ? 'danger'
                                : 'success'),
                        TextEntry::make('two_factor_confirmed_at')
                            ->label('Autentificare în doi pași')
                            ->state(fn (User $record): string => $record->two_factor_confirmed_at !== null
                                ? 'Activată'
                                : 'Neactivată')
                            ->badge()
                            ->color(fn (User $record): string => $record->two_factor_confirmed_at !== null
                                ? 'success'
                                : 'gray'),
                        TextEntry::make('suspended_at')
                            ->label('Suspendat la')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('suspended_reason')
                            ->label('Motiv suspendare')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('last_login_at')
                            ->label('Ultima autentificare')
                            ->dateTime()
                            ->placeholder('Niciodată'),
                        TextEntry::make('last_login_ip')
                            ->label('Ultimul IP')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make('Roluri')
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('Roluri')
                            ->badge()
                            ->color(fn (string $state): string => UserResource::roleBadgeColor($state)),
                    ]),
                Section::make('Profil platformă')
                    ->schema([
                        TextEntry::make('host.kyc_status')
                            ->label('KYC gazdă')
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('host.stripe_account_status')
                            ->label('Stripe gazdă')
                            ->placeholder('—'),
                        TextEntry::make('provider.kyc_status')
                            ->label('KYC firmă')
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('provider.is_active')
                            ->label('Firmă activă')
                            ->state(fn (User $record): ?string => $record->provider === null
                                ? null
                                : ($record->provider->is_active ? 'Da' : 'Nu'))
                            ->badge()
                            ->color(fn (User $record): string => $record->provider?->is_active
                                ? 'success'
                                : 'gray')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
            ]);
    }
}
