<?php

namespace App\Filament\Provider\Resources\Teams\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nume echipă')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Descriere')
                    ->columnSpanFull(),
            ]);
    }
}
