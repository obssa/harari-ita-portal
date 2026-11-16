<?php

namespace App\Filament\Resources\Startups\Pages;

use App\Domain\Innovation\Actions\SaveStartup;
use App\Domain\Innovation\Models\Startup;
use App\Filament\Resources\Startups\StartupResource;
use App\Filament\Support\CurrentUser;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStartup extends CreateRecord
{
    protected static string $resource = StartupResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveStartup::class)->handle(new Startup, $data, CurrentUser::get());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
