<?php

namespace App\Filament\Resources\Disputes\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DisputeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference')
                    ->required(),
                Select::make('booking_id')
                    ->relationship('booking', 'id'),
                Select::make('task_id')
                    ->relationship('task', 'title'),
                Select::make('opener_id')
                    ->relationship('opener', 'name')
                    ->required(),
                TextInput::make('category')
                    ->required(),
                TextInput::make('subject')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('open'),
                Select::make('assigned_admin_id')
                    ->relationship('assignedAdmin', 'name'),
                Textarea::make('resolution')
                    ->columnSpanFull(),
                TextInput::make('refund_amount')
                    ->numeric(),
                DateTimePicker::make('resolved_at'),
            ]);
    }
}
