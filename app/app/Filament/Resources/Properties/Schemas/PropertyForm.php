<?php

namespace App\Filament\Resources\Properties\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class PropertyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('host_id')
                    ->relationship('host', 'id')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('property_type')
                    ->required(),
                TextInput::make('address')
                    ->required(),
                TextInput::make('city')
                    ->required(),
                TextInput::make('county'),
                TextInput::make('country')
                    ->required()
                    ->default('RO'),
                TextInput::make('postal_code'),
                TextInput::make('base_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('currency')
                    ->required()
                    ->default('RON'),
                TextInput::make('cleaning_fee')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('max_guests')
                    ->required()
                    ->numeric()
                    ->default(2),
                TextInput::make('bedrooms')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('beds')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('bathrooms')
                    ->required()
                    ->numeric()
                    ->default(1),
                TimePicker::make('check_in_time')
                    ->required(),
                TimePicker::make('check_out_time')
                    ->required(),
                TextInput::make('min_nights')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('max_nights')
                    ->required()
                    ->numeric()
                    ->default(30),
                Textarea::make('house_rules')
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('rating_avg')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('reviews_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('location'),
            ]);
    }
}
