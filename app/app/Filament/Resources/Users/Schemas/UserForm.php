<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitate')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nume')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true),
                        TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->nullable(),
                        Select::make('locale')
                            ->label('Limbă')
                            ->options([
                                'ro' => 'Română',
                                'en' => 'English',
                            ])
                            ->default('ro')
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Securitate')
                    ->schema([
                        TextInput::make('password')
                            ->label('Parolă')
                            ->password()
                            ->revealable()
                            ->required(fn (?User $record): bool => $record === null)
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->helperText(fn (?User $record): string => $record === null
                                ? 'Parola va fi hash-uită automat la salvare.'
                                : 'Lasă gol pentru a păstra parola actuală.'),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email verificat la')
                            ->nullable()
                            ->helperText('Gol = cont neverificat.'),
                    ])
                    ->columns(2),
                Section::make('Roluri')
                    ->schema([
                        Select::make('roles')
                            ->label('Roluri')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Host = gazdă, Provider = firmă de servicii, Guest = oaspete, SuperAdmin = administrator platformă.'),
                    ]),
            ]);
    }
}
