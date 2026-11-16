<?php

namespace App\Filament\Resources\Programmes\Pages;

use App\Domain\Innovation\Actions\SaveProgramme;
use App\Domain\Innovation\Models\Cohort;
use App\Domain\Innovation\Models\Programme;
use App\Filament\Resources\Programmes\ProgrammeResource;
use App\Filament\Support\CurrentUser;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\ViewOnSite;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProgramme extends EditRecord
{
    protected static string $resource = ProgrammeResource::class;

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
        /** @var Programme $record */
        $record = $this->getRecord();
        $data['translations'] = DirectoryFields::fill($record, ['title', ...SaveProgramme::TEXT_FIELDS]);
        $data['questions'] = $record->questions ?? [];
        $data['cohorts'] = $record->cohorts()->get()->map(fn (Cohort $cohort): array => [
            'id' => $cohort->id,
            'name' => $cohort->name,
            'opens_at' => $cohort->opens_at->toDateTimeString(),
            'closes_at' => $cohort->closes_at->toDateTimeString(),
            'starts_on' => $cohort->starts_on?->toDateString(),
            'ends_on' => $cohort->ends_on?->toDateString(),
            'places' => $cohort->places,
        ])->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Programme $record */
        return app(SaveProgramme::class)->handle($record, $data, CurrentUser::get());
    }
}
