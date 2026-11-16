<?php

namespace App\Filament\Resources\Programmes\Pages;

use App\Domain\Innovation\Actions\SaveProgramme;
use App\Domain\Innovation\Models\Programme;
use App\Filament\Resources\Programmes\ProgrammeResource;
use App\Filament\Support\CurrentUser;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProgramme extends CreateRecord
{
    protected static string $resource = ProgrammeResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveProgramme::class)->handle(new Programme, $data, CurrentUser::get());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
