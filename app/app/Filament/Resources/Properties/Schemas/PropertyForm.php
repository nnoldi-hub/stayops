<?php

namespace App\Filament\Resources\Properties\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
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
                Select::make('property_type')
                    ->options([
                        'apartment' => 'Apartament',
                        'house' => 'Casă',
                        'villa' => 'Vilă',
                        'studio' => 'Studio',
                        'cabin' => 'Cabană',
                    ])
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
                    ->prefix('RON'),
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
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Publicată',
                        'paused' => 'Pauzată',
                        'archived' => 'Arhivată',
                    ])
                    ->required()
                    ->default('draft'),
                SpatieMediaLibraryFileUpload::make('photos')
                    ->collection('photos')
                    ->disk('public')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->responsiveImages()
                    ->maxSize(8192)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('floor_plans')
                    ->collection('floor_plans')
                    ->disk('public')
                    ->maxSize(8192)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                    ->columnSpanFull(),
            ]);
    }
}

