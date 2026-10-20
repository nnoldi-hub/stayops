<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Actions\SendPasswordResetAction;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUsers extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SendPasswordResetAction::make(),
            DeleteAction::make()
                ->visible(function (): bool {
                    $record = $this->getRecord();

                    if (! $record instanceof User) {
                        return false;
                    }

                    return auth()->user()?->can('users.delete') === true
                        && UserResource::deletionBlocker($record) === null;
                }),
        ];
    }
}
