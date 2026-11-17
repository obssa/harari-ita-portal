<?php

namespace App\Filament\Resources\CourseCohorts\Pages;

use App\Filament\Resources\CourseCohorts\CourseCohortResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCourseCohorts extends ListRecords
{
    protected static string $resource = CourseCohortResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
