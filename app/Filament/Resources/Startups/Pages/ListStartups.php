<?php

namespace App\Filament\Resources\Startups\Pages;

use App\Domain\Innovation\Models\Startup;
use App\Filament\Resources\Startups\StartupResource;
use App\Filament\Support\CsvDownload;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStartups extends ListRecords
{
    protected static string $resource = StartupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ADR-0038: the directory as listed, without the founders' contact details.
            CsvDownload::action('startups.view', 'startups', 'innovation', fn () => $this->getFilteredTableQuery()->with(['translations', 'sector.translations']),
                array_map(fn (string $k): string => __("admin.export.{$k}"), ['reference', 'name', 'sector', 'stage', 'founded', 'team_size', 'area', 'website', 'label_status', 'listing_status', 'listed_at']),
                fn (Startup $s): array => [
                    $s->reference, $s->name(), (string) $s->sector?->name(), $s->stage->label(), (string) $s->founded_year, (string) $s->team_size,
                    (string) $s->area, (string) $s->website, $s->label_status->label(), $s->listing_status->label(), CsvDownload::time($s->listed_at),
                ]),
            CreateAction::make(),
        ];
    }
}
