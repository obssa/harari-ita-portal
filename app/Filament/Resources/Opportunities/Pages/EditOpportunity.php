<?php

namespace App\Filament\Resources\Opportunities\Pages;

use App\Domain\Opportunities\Actions\SaveOpportunity;
use App\Domain\Opportunities\Models\Opportunity;
use App\Filament\Resources\Opportunities\OpportunityResource;
use App\Filament\Support\CurrentUser;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\DraftCampaignAction;
use App\Filament\Support\ViewOnSite;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

class EditOpportunity extends EditRecord
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [...ViewOnSite::header(), DraftCampaignAction::make(), DeleteAction::make(), RestoreAction::make()];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Opportunity $opportunity */
        $opportunity = $this->getRecord();
        $data['translations'] = DirectoryFields::fill($opportunity, ['title', ...SaveOpportunity::TEXT_FIELDS]);
        $data['documents'] = $opportunity->documents()->pluck('documents.id')->map(fn (mixed $id): string => (string) $id)->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Opportunity $record */
        return app(SaveOpportunity::class)->handle($record, $data, CurrentUser::get());
    }

    /** The deadline shown again after an extension was logged below. */
    #[On('opportunity-log-changed')]
    public function refreshDeadline(): void
    {
        $this->getRecord()->refresh();
        $this->refreshFormData(['closes_at']);
    }
}
