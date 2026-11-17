<?php

namespace App\Filament\Resources\Opportunities\Pages;

use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Filament\Resources\Opportunities\OpportunityResource;
use App\Filament\Support\CsvDownload;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOpportunities extends ListRecords
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ADR-0038: the opportunities with their dates, state and log size.
            CsvDownload::action('opportunities.view', 'opportunities', 'opportunities', fn () => $this->getFilteredTableQuery()->with(['translations', 'office.translations'])->withCount('addenda'),
                array_map(fn (string $k): string => __("admin.export.{$k}"), ['reference', 'type', 'title', 'directorate', 'opens', 'deadline', 'first_deadline', 'state', 'log_entries', 'languages']),
                fn (Opportunity $o): array => [
                    (string) $o->reference, $o->type->label(), $o->title(), (string) $o->office?->name(), CsvDownload::time($o->opens_at), CsvDownload::time($o->closes_at),
                    CsvDownload::time($o->original_closes_at), __('opportunities.state.'.$o->state()), (string) $o->getAttribute('addenda_count'),
                    $o->translations->filter(fn ($t): bool => PublicDirectory::live($t))->pluck('locale')->implode(', '),
                ]),
            CreateAction::make(),
        ];
    }
}
