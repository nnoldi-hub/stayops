<?php

namespace App\Filament\Actions;

use App\Models\User;
use App\Services\ImpersonationService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

class ImpersonateUserAction
{
    public static function make(): Action
    {
        return Action::make('impersonate')
            ->label('Intră în cont')
            ->icon('heroicon-o-user-circle')
            ->color('warning')
            ->modalHeading(fn (User $record): string => 'Intră în contul: '.$record->name)
            ->modalDescription('Vei fi autentificat ca acest utilizator. Acțiunea este înregistrată în jurnal.')
            ->form([
                TextInput::make('reason')
                    ->label('Motiv')
                    ->placeholder('Suport tehnic')
                    ->required(),
            ])
            ->modalSubmitActionLabel('Intră în cont')
            ->visible(function (User $record): bool {
                $admin = auth()->user();

                if (! $admin instanceof User || ! $admin->can('users.impersonate')) {
                    return false;
                }

                return ! $record->is($admin) && ! $record->hasRole('SuperAdmin');
            })
            ->action(function (User $record, array $data, ImpersonationService $service): void {
                $service->start(
                    $record,
                    (string) ($data['reason'] ?? 'Suport tehnic'),
                );
            })
            ->successRedirectUrl(route('impersonation.landing'))
            ->successNotificationTitle('Ai intrat în contul utilizatorului');
    }
}
