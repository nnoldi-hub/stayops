<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PlanForm
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
                Textarea::make('description_ro')
                    ->columnSpanFull(),
                Textarea::make('description_en')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('currency')
                    ->required()
                    ->default('RON'),
                TextInput::make('interval')
                    ->required(),
                TextInput::make('features'),
                TextInput::make('max_properties')
                    ->numeric(),
                TextInput::make('platform_fee_pct')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('stripe_price_id'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
