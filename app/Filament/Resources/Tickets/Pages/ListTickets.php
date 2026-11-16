<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('board')
                ->label(__('support.admin.board'))
                ->icon(Heroicon::OutlinedViewColumns)
                ->color('gray')
                ->url(TicketResource::getUrl('board')),
            CreateAction::make()->label(__('support.admin.record')),
        ];
    }
}
