<?php

namespace App\Filament\Resources\Pillars\Pages;

use App\Domain\Organisation\Actions\SavePillar;
use App\Domain\Organisation\Models\Pillar;
use App\Filament\Resources\Pillars\PillarResource;
use App\Filament\Support\CurrentUser;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePillar extends CreateRecord
{
    protected static string $resource = PillarResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SavePillar::class)->handle(new Pillar, $data, CurrentUser::get());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
