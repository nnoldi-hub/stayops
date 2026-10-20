<?php

namespace App\Filament\Provider\Resources\Tasks\Pages;

use App\Filament\Provider\Resources\TaskResource;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
