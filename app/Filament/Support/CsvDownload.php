<?php

namespace App\Filament\Support;

use App\Domain\Events\Support\RegistrationExport;
use App\Models\User;
use App\Services\Calendar\EthiopianCalendar;
use Carbon\CarbonImmutable;
use Closure;
use DateTimeInterface;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports of admin lists as CSV (PLAN.md §6, ADR-0038): a byte-order mark so spreadsheets read Ethiopic correctly,
 * cells a spreadsheet would run as a formula neutralised, the rows streamed in chunks, and each export recorded in
 * the activity log with its count only, never the data.
 */
final class CsvDownload
{
    /**
     * A header action that exports the list's filtered records.
     *
     * @param  Closure  $query  returns the records to export, already filtered and scoped
     * @param  list<string>  $headings
     * @param  Closure  $row  turns one record into its cells
     */
    public static function action(string $permission, string $name, string $logName, Closure $query, array $headings, Closure $row): Action
    {
        return Action::make('export')
            ->label(__('admin.export.csv'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->visible(fn (): bool => CurrentUser::get()->can($permission))
            ->action(fn (): StreamedResponse => self::stream(CurrentUser::get(), $permission, $name, $logName, $query(), $headings, $row));
    }

    /**
     * @param  Builder<Model>  $query
     * @param  list<string>  $headings
     * @param  Closure  $row  turns one record into its cells
     */
    public static function stream(User $actor, string $permission, string $name, string $logName, Builder $query, array $headings, Closure $row): StreamedResponse
    {
        abort_unless($actor->can($permission), 403);
        activity($logName)->causedBy($actor)->event('exported')->withProperties(['list' => $name, 'count' => (clone $query)->count()])->log('Exported '.$name);

        return response()->streamDownload(function () use ($query, $headings, $row): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\u{FEFF}");
            fputcsv($out, $headings, escape: '');
            $query->reorder()->orderBy($query->getModel()->getQualifiedKeyName())->chunk(500, function ($records) use ($out, $row): void {
                foreach ($records as $record) {
                    $cells = array_map(fn (mixed $cell): string => is_scalar($cell) ? (string) $cell : '', array_values((array) $row($record)));
                    fputcsv($out, array_map(RegistrationExport::cell(...), $cells), escape: '');
                }
            });
            fclose($out);
        }, $name.'-'.CarbonImmutable::now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** A time in Ethiopia's time zone, or empty. */
    public static function time(?DateTimeInterface $at): string
    {
        return $at === null ? '' : CarbonImmutable::instance($at)->setTimezone(EthiopianCalendar::timezone())->format('Y-m-d H:i');
    }
}
