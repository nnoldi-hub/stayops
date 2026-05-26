<?php

namespace App\Filament\Resources\Amenities\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AmenityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->required(),
                TextInput::make('name_ro')
                    ->required(),
                TextInput::make('name_en')
                    ->required(),
                TextInput::make('icon'),
                TextInput::make('category'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
