<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Domain\Support\Actions\OpenTicket;
use App\Filament\Resources\Tickets\TicketResource;
use App\Filament\Support\CurrentUser;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/** Staff record a ticket they took by phone, email or at the desk (ADR-0017). */
class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(OpenTicket::class)->handle($data, [], (string) ($data['locale'] ?? app()->getLocale()), CurrentUser::get());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
