<?php

namespace App\Filament\Provider\Resources\Teams\Pages;

use App\Filament\Provider\Resources\Teams\TeamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['provider_id'] = auth()->user()->provider->id;

        return $data;
    }
}
