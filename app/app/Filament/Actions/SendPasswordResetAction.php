<?php

namespace App\Filament\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Password;

class SendPasswordResetAction
{
    public static function make(): Action
    {
        return Action::make('sendPasswordReset')
            ->label('Reset parolă')
            ->icon('heroicon-o-key')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Trimite linkul de resetare a parolei')
            ->modalDescription('Utilizatorul va primi pe email un link valabil pentru setarea unei parole noi.')
            ->modalSubmitActionLabel('Trimite linkul')
            ->visible(fn (User $record): bool => auth()->user()?->can('users.update') === true
                && filled($record->email))
            ->action(function (User $record): void {
                Password::sendResetLink(['email' => $record->email]);
            })
            ->successNotificationTitle('Linkul de resetare a fost trimis');
    }
}
