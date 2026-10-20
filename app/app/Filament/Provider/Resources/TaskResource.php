<?php

namespace App\Filament\Provider\Resources;

use App\Filament\Provider\Resources\Tasks\Pages\EditTask;
use App\Filament\Provider\Resources\Tasks\Pages\ListTasks;
use App\Filament\Provider\Resources\Tasks\Schemas\TaskForm;
use App\Filament\Provider\Resources\Tasks\Tables\TasksTable;
use App\Models\Task;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends \Filament\Resources\Resource<Task>
 */
class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationLabel = 'Sarcini';

    protected static ?string $modelLabel = 'Sarcină';

    protected static ?string $pluralModelLabel = 'Sarcini';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\UnitEnum|null $navigationGroup = 'Operațional';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->visibleToProviderUser(auth()->id())
            ->with(['property', 'booking', 'provider']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return TaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }
}
