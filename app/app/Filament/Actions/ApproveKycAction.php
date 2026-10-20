<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class ApproveKycAction
{
    public static function make(string $permission): Action
    {
        return Action::make('approveKyc')
            ->label('Aprobă KYC')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Aprobă verificarea KYC')
            ->modalDescription('Contul va fi marcat ca verificat, iar proprietățile gazdei devin publice.')
            ->modalSubmitActionLabel('Aprobă')
            ->visible(fn (Model $record): bool => auth()->user()?->can($permission) === true
                && $record->getAttribute('kyc_status') !== 'approved')
            ->action(fn (Model $record) => $record->update(['kyc_status' => 'approved']))
            ->successNotificationTitle('KYC aprobat');
    }
}
