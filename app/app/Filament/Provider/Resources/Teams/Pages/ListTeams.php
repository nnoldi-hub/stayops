<?php

namespace App\Filament\Provider\Resources\Teams\Pages;

use App\Filament\Provider\Resources\Teams\TeamResource;
use Filament\Resources\Pages\ListRecords;

class ListTeams extends ListRecords
{
    protected static string $resource = TeamResource::class;
}
