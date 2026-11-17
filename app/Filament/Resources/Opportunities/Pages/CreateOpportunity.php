<?php

namespace App\Filament\Resources\Opportunities\Pages;

use App\Domain\Opportunities\Actions\SaveOpportunity;
use App\Domain\Opportunities\Models\Opportunity;
use App\Filament\Resources\Opportunities\OpportunityResource;
use App\Filament\Support\CurrentUser;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveOpportunity::class)->handle(new Opportunity, $data, CurrentUser::get());
    }

    /** The log is kept on the next page. */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
