<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceItems extends ListRecords
{
    protected static string $resource = ServiceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
