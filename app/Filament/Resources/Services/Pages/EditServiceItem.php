<?php

namespace App\Filament\Resources\Services\Pages;

use App\Domain\Services\Actions\SaveServiceItem;
use App\Domain\Services\Models\ServiceItem;
use App\Filament\Resources\Services\ServiceItemResource;
use App\Filament\Support\CurrentUser;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\ViewOnSite;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditServiceItem extends EditRecord
{
    protected static string $resource = ServiceItemResource::class;

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
        /** @var ServiceItem $record */
        $record = $this->getRecord();
        $data['translations'] = DirectoryFields::fill($record, ['title', ...SaveServiceItem::TEXT_FIELDS, 'faq']);
        $data['reviewed_at'] = $record->reviewed_at?->toDateString();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var ServiceItem $record */
        return app(SaveServiceItem::class)->handle($record, $data, CurrentUser::get());
    }
}
