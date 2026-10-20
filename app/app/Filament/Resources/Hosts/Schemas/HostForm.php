<?php

namespace App\Filament\Resources\Hosts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('company_name'),
                TextInput::make('cui'),
                TextInput::make('registration_number'),
                TextInput::make('billing_address'),
                TextInput::make('billing_city'),
                TextInput::make('billing_country')
                    ->required()
                    ->default('RO'),
                TextInput::make('vat_number'),
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
                    ->directory('kyc/hosts')
                    ->reorderable()
                    ->openable()
                    ->nullable(),
                TextInput::make('stripe_account_id'),
                TextInput::make('stripe_account_status')
                    ->required()
                    ->default('none'),
                DateTimePicker::make('onboarded_at'),
            ]);
    }
}
