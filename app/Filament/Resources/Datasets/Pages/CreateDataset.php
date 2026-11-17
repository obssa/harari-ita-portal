<?php

namespace App\Filament\Resources\Datasets\Pages;

use App\Domain\OpenData\Actions\SaveDataset;
use App\Domain\OpenData\Models\Dataset;
use App\Filament\Resources\Datasets\DatasetResource;
use App\Filament\Support\CurrentUser;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDataset extends CreateRecord
{
    protected static string $resource = DatasetResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveDataset::class)->handle(new Dataset, $data, CurrentUser::get());
    }

    /** The files are added on the next page. */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
