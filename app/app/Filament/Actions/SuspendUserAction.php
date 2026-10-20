<?php

namespace App\Filament\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

class SuspendUserAction
{
    public static function make(): Action
    {
        return Action::make('suspend')
            ->label('Suspendă')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->modalHeading(fn (User $record): string => 'Suspendă contul: '.$record->name)
            ->modalDescription('Utilizatorul va fi deconectat și nu va mai putea accesa platforma până la reactivare.')
            ->form([
                TextInput::make('reason')
                    ->label('Motiv')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ex.: încălcare a termenilor platformei'),
            ])
            ->modalSubmitActionLabel('Suspendă contul')
            ->visible(function (User $record): bool {
                $admin = auth()->user();

                if (! $admin instanceof User || ! $admin->can('users.update')) {
                    return false;
                }

                return ! $record->isSuspended()
                    && ! $record->is($admin)
                    && ! $record->hasRole('SuperAdmin');
            })
            ->action(function (User $record, array $data): void {
                $record->suspend((string) ($data['reason'] ?? 'Fără motiv'));
            })
            ->successNotificationTitle('Contul a fost suspendat');
    }
}
