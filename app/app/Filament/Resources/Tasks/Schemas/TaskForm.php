<?php

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('property_id')
                    ->relationship('property', 'title')
                    ->required(),
                Select::make('booking_id')
                    ->relationship('booking', 'id'),
                TextInput::make('type')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('provider_id')
                    ->relationship('provider', 'id'),
                TextInput::make('assigned_to_user_id')
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('open'),
                TextInput::make('priority')
                    ->required()
                    ->default('normal'),
                DateTimePicker::make('scheduled_at'),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('completed_at'),
                DateTimePicker::make('verified_at'),
                TextInput::make('estimated_cost')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('actual_cost')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('currency')
                    ->required()
                    ->default('RON'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('checklist'),
            ]);
    }
}
