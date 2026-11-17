<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('board')
                ->label(__('innovation.admin.board'))
                ->icon(Heroicon::OutlinedViewColumns)
                ->color('gray')
                ->url(ApplicationResource::getUrl('board')),
        ];
    }
}
