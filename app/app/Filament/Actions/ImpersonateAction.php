<?php

namespace App\Filament\Actions;

use App\Models\Host;
use App\Models\Provider;
use App\Models\User;
use App\Services\ImpersonationService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

class ImpersonateAction
{
    public static function make(): Action
    {
        return Action::make('impersonate')
            ->label('Intră în cont')
            ->icon('heroicon-o-user-circle')
            ->color('warning')
            ->modalHeading('Intră în contul utilizatorului')
            ->modalDescription('Vei fi autentificat ca acest utilizator. Acțiunea este înregistrată în jurnal.')
            ->form([
                TextInput::make('reason')
                    ->label('Motiv')
                    ->placeholder('Suport tehnic')
                    ->required(),
            ])
            ->modalSubmitActionLabel('Intră în cont')
            ->visible(fn (Host|Provider|null $record): bool => self::isVisible($record))
            ->action(function (Host|Provider $record, array $data, ImpersonationService $service): void {
                abort_unless($record->user instanceof User, 404);

                $service->start(
                    $record->user,
                    (string) ($data['reason'] ?? 'Suport tehnic'),
                );
            })
            ->successRedirectUrl(route('impersonation.landing'))
            ->successNotificationTitle('Ai intrat în contul utilizatorului');
    }

    private static function isVisible(Host|Provider|null $record): bool
    {
        if ($record === null || $record->user === null) {
            return false;
        }

        $admin = auth()->user();

        if (! $admin?->can('users.impersonate')) {
            return false;
        }

        if ($record->user->is($admin) || $record->user->hasRole('SuperAdmin')) {
            return false;
        }

        return true;
    }
}
