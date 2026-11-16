<?php

namespace App\Filament\Resources\Pillars\Pages;

use App\Domain\Organisation\Actions\SavePillar;
use App\Domain\Organisation\Models\Pillar;
use App\Filament\Resources\Pillars\PillarResource;
use App\Filament\Support\CurrentUser;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\ViewOnSite;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPillar extends EditRecord
{
    protected static string $resource = PillarResource::class;

    protected function getHeaderActions(): array
    {
        return [...ViewOnSite::header(), DeleteAction::make(), RestoreAction::make()];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Pillar $record */
        $record = $this->getRecord();
        $data['translations'] = DirectoryFields::fill($record, ['summary']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Pillar $record */
        return app(SavePillar::class)->handle($record, $data, CurrentUser::get());
    }
}
