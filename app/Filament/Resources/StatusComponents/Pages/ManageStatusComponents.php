<?php

namespace App\Filament\Resources\StatusComponents\Pages;

use App\Filament\Resources\StatusComponents\StatusComponentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStatusComponents extends ManageRecords
{
    protected static string $resource = StatusComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
