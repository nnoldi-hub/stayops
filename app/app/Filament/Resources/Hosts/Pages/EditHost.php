<?php

namespace App\Filament\Resources\Hosts\Pages;

use App\Filament\Resources\Hosts\HostResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditHost extends EditRecord
{
    protected static string $resource = HostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
