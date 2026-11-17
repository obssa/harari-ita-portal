<?php

namespace App\Filament\Resources\Datasets\Pages;

use App\Domain\OpenData\Actions\SaveDataset;
use App\Domain\OpenData\Models\Dataset;
use App\Filament\Resources\Datasets\DatasetResource;
use App\Filament\Support\CurrentUser;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\ViewOnSite;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDataset extends EditRecord
{
    protected static string $resource = DatasetResource::class;

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
        /** @var Dataset $dataset */
        $dataset = $this->getRecord();
        $data['translations'] = DirectoryFields::fill($dataset, ['title', 'summary', 'methodology', 'coverage']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Dataset $record */
        return app(SaveDataset::class)->handle($record, $data, CurrentUser::get());
    }
}
