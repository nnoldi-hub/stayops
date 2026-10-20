<?php

namespace App\Filament\Resources\Providers\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('company_name')
                    ->required(),
                TextInput::make('cui'),
                CheckboxList::make('service_types')
                    ->label('Tipuri de servicii')
                    ->options([
                        'cleaning' => 'Curățenie',
                        'repair' => 'Reparații',
                        'maintenance' => 'Mentenanță generală',
                        'other' => 'Altele',
                    ])
                    ->columns(2),
                TextInput::make('coverage_cities')
                    ->label('Orașuri acoperite')
                    ->helperText('Separate prin virgulă. Gol = acoperire națională.')
                    ->afterStateHydrated(function ($state, $set): void {
                        $set('coverage_cities', is_array($state) ? implode(', ', $state) : $state);
                    })
                    ->dehydrateStateUsing(fn ($state): array => array_values(array_filter(array_map(
                        'trim',
                        is_array($state) ? $state : explode(',', (string) $state),
                    ))))
                    ->nullable(),
                Select::make('kyc_status')
                    ->label('Status KYC')
                    ->required()
                    ->default('pending')
                    ->options([
                        'pending' => 'În așteptare',
                        'submitted' => 'Trimis spre verificare',
                        'approved' => 'Aprobat',
                        'rejected' => 'Respins',
                    ]),
                FileUpload::make('kyc_documents')
                    ->label('Documente KYC')
                    ->multiple()
                    ->disk('public')
                    ->directory('kyc/providers')
                    ->reorderable()
                    ->openable()
                    ->nullable(),
                TextInput::make('rating_avg')
                    ->numeric()
                    ->default(0),
                TextInput::make('jobs_completed')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Activ')
                    ->default(true),
            ]);
    }
}
