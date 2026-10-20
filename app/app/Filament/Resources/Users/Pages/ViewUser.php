<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Actions\ActivateUserAction;
use App\Filament\Actions\ImpersonateUserAction;
use App\Filament\Actions\SendPasswordResetAction;
use App\Filament\Actions\SuspendUserAction;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SuspendUserAction::make(),
            ActivateUserAction::make(),
            ImpersonateUserAction::make(),
            SendPasswordResetAction::make(),
            EditAction::make(),
        ];
    }
}
