<?php

namespace App\Filament\Resources\Hosts\Schemas;

use Filament\Forms\Components\DateTimePicker;
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
                TextInput::make('kyc_status')
                    ->required()
                    ->default('pending'),
                TextInput::make('kyc_documents'),
                TextInput::make('stripe_account_id'),
                TextInput::make('stripe_account_status')
                    ->required()
                    ->default('none'),
                DateTimePicker::make('onboarded_at'),
            ]);
    }
}
