<?php

namespace App\Filament\Resources\Incidents\Pages;

use App\Domain\Status\Actions\SaveIncident;
use App\Domain\Status\Models\Incident;
use App\Filament\Resources\Incidents\IncidentResource;
use App\Filament\Support\CurrentUser;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateIncident extends CreateRecord
{
    protected static string $resource = IncidentResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveIncident::class)->handle(new Incident, $data, CurrentUser::get());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
