<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference')
                    ->required(),
                Select::make('property_id')
                    ->relationship('property', 'title')
                    ->required(),
                Select::make('guest_id')
                    ->relationship('guest', 'name'),
                TextInput::make('guest_name'),
                TextInput::make('guest_email')
                    ->email(),
                TextInput::make('guest_phone')
                    ->tel(),
                DatePicker::make('check_in')
                    ->required(),
                DatePicker::make('check_out')
                    ->required(),
                TextInput::make('nights')
                    ->required()
                    ->numeric(),
                TextInput::make('guests_count')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('adults')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('children')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('nightly_rate')
                    ->required()
                    ->numeric(),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric(),
                TextInput::make('cleaning_fee')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('platform_fee')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('host_payout')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('currency')
                    ->required()
                    ->default('RON'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('source')
                    ->required()
                    ->default('direct'),
                TextInput::make('external_id'),
                TextInput::make('stripe_payment_intent_id'),
                TextInput::make('payment_status')
                    ->required()
                    ->default('pending'),
                Textarea::make('special_requests')
                    ->columnSpanFull(),
                Textarea::make('internal_notes')
                    ->columnSpanFull(),
                DateTimePicker::make('cancelled_at'),
                TextInput::make('cancellation_reason'),
            ]);
    }
}
