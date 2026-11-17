<?php

namespace App\Filament\Resources\Datasets\Pages;

use App\Domain\OpenData\Models\Dataset;
use App\Domain\OpenData\Models\DatasetDistribution;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Filament\Resources\Datasets\DatasetResource;
use App\Filament\Support\CsvDownload;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDatasets extends ListRecords
{
    protected static string $resource = DatasetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ADR-0038: the catalogue with its freshness and downloads.
            CsvDownload::action('datasets.view', 'datasets', 'open_data', fn () => $this->getFilteredTableQuery()->with(['translations', 'office.translations', 'distributions']),
                array_map(fn (string $k): string => __("admin.export.{$k}"), ['title', 'publisher', 'licence', 'frequency', 'updated_on', 'next_due', 'overdue', 'downloads', 'formats', 'languages']),
                fn (Dataset $d): array => [
                    $d->title(), (string) $d->office?->name(), $d->licence->label(), $d->frequency->label(), (string) $d->data_updated_on?->toDateString(),
                    (string) $d->nextUpdateDue()?->toDateString(), $d->isOverdue() ? __('admin.export.yes') : __('admin.export.no'), (string) $d->download_count,
                    $d->distributions->map(fn (DatasetDistribution $x): string => $x->format->label())->unique()->implode(', '),
                    $d->translations->filter(fn ($t): bool => PublicDirectory::live($t))->pluck('locale')->implode(', '),
                ]),
            CreateAction::make(),
        ];
    }
}
