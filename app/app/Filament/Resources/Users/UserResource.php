<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUsers;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Utilizatori';

    protected static ?string $modelLabel = 'Utilizator';

    protected static ?string $pluralModelLabel = 'Utilizatori';

    protected static string|\UnitEnum|null $navigationGroup = 'Conturi';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    /**
     * Returnează motivul pentru care ștergerea contului este blocată, sau null dacă e permisă.
     */
    public static function deletionBlocker(User $record): ?string
    {
        $admin = auth()->user();

        if ($admin instanceof User && $admin->is($record)) {
            return 'Nu îți poți șterge propriul cont.';
        }

        if ($record->hasRole('SuperAdmin')) {
            return 'Conturile SuperAdmin nu pot fi șterse din interfață.';
        }

        if ($record->host()->exists() || $record->provider()->exists()) {
            return 'Contul are un profil Gazdă/Firmă asociat — șterge mai întâi profilul.';
        }

        return null;
    }

    public static function roleBadgeColor(string $role): string
    {
        return match ($role) {
            'SuperAdmin' => 'danger',
            'Host' => 'info',
            'Provider' => 'success',
            default => 'gray',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUsers::route('/{record}/edit'),
        ];
    }
}
