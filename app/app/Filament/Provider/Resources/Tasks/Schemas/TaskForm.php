<?php

namespace App\Filament\Provider\Resources\Tasks\Schemas;

use App\Models\TeamMember;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('assigned_to_user_id')
                    ->label('Atribuit către')
                    ->options(fn (): array => self::assigneeOptions())
                    ->searchable()
                    ->preload()
                    ->placeholder('Neatribuit'),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Deschisă',
                        'assigned' => 'Atribuită',
                        'in_progress' => 'În lucru',
                        'completed' => 'Finalizată',
                        'verified' => 'Verificată',
                        'cancelled' => 'Anulată',
                    ])
                    ->required(),
                TextInput::make('actual_cost')
                    ->label('Cost real')
                    ->numeric()
                    ->prefix('RON'),
                TextInput::make('currency')
                    ->label('Monedă')
                    ->default('RON')
                    ->required(),
                Textarea::make('notes')
                    ->label('Note')
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('before_photos')
                    ->label('Poze înainte')
                    ->collection('before_photos')
                    ->image()
                    ->multiple()
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('after_photos')
                    ->label('Poze după (dovada muncii)')
                    ->collection('after_photos')
                    ->image()
                    ->multiple()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Users that can be assigned tasks: members of the current provider's teams.
     *
     * @return array<int, string>
     */
    protected static function assigneeOptions(): array
    {
        $provider = auth()->user()?->provider;

        if (! $provider) {
            return [];
        }

        $userIds = TeamMember::query()
            ->whereHas('team', fn ($query) => $query->where('provider_id', $provider->id))
            ->pluck('user_id');

        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
