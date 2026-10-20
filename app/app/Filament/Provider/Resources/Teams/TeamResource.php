<?php

namespace App\Filament\Provider\Resources\Teams;

use App\Filament\Provider\Resources\Teams\Pages\CreateTeam;
use App\Filament\Provider\Resources\Teams\Pages\EditTeam;
use App\Filament\Provider\Resources\Teams\Pages\ListTeams;
use App\Filament\Provider\Resources\Teams\RelationManagers\MembersRelationManager;
use App\Filament\Provider\Resources\Teams\Schemas\TeamForm;
use App\Filament\Provider\Resources\Teams\Tables\TeamsTable;
use App\Models\Team;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends \Filament\Resources\Resource<Team>
 */
class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationLabel = 'Echipe';

    protected static ?string $modelLabel = 'Echipă';

    protected static ?string $pluralModelLabel = 'Echipe';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Operațional';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('provider', fn (Builder $q) => $q->where('user_id', auth()->id()));
    }

    public static function form(Schema $schema): Schema
    {
        return TeamForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeamsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeams::route('/'),
            'create' => CreateTeam::route('/create'),
            'edit' => EditTeam::route('/{record}/edit'),
        ];
    }
}
