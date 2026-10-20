<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class RejectKycAction
{
    public static function make(string $permission): Action
    {
        return Action::make('rejectKyc')
            ->label('Respinge KYC')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Respinge verificarea KYC')
            ->modalDescription('Contul rămâne neverificat; proprietățile gazdei nu vor fi publice.')
            ->modalSubmitActionLabel('Respinge')
            ->visible(fn (Model $record): bool => auth()->user()?->can($permission) === true
                && $record->getAttribute('kyc_status') !== 'rejected')
            ->action(fn (Model $record) => $record->update(['kyc_status' => 'rejected']))
            ->successNotificationTitle('KYC respins');
    }
}
