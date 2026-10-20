<?php

namespace App\Filament\Actions;

use App\Models\User;
use Filament\Actions\Action;

class ActivateUserAction
{
    public static function make(): Action
    {
        return Action::make('activate')
            ->label('Reactivează')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Reactivează contul')
            ->modalDescription('Contul își reia imediat accesul la platformă.')
            ->modalSubmitActionLabel('Reactivează')
            ->visible(function (User $record): bool {
                $admin = auth()->user();

                if (! $admin instanceof User || ! $admin->can('users.update')) {
                    return false;
                }

                return $record->isSuspended();
            })
            ->action(function (User $record): void {
                $record->activate();
            })
            ->successNotificationTitle('Contul a fost reactivat');
    }
}
