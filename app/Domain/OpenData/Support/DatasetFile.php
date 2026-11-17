<?php

namespace App\Domain\OpenData\Support;

use App\Domain\OpenData\Enums\DistributionFormat;
use DateTimeInterface;
use Generator;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Throwable;

/**
 * The facts of a dataset file (ADR-0025): its size and SHA-256 checksum and, for tabular files, the number of rows,
 * the column names, a preview of the first rows and the columns whose names look like personal data. Reading is
 * streamed for CSV and XLSX; a JSON file is read whole only up to MAX_JSON_BYTES.
 */
final class DatasetFile
{
    public const PREVIEW_ROWS = 10;

    public const PREVIEW_COLUMNS = 20;

    public const CELL_LENGTH = 120;

    public const MAX_JSON_BYTES = 20 * 1024 * 1024;

    /** Column names that suggest personal data: a warning for the Data Steward, never a block. */
    private const PERSONAL = '/(^|[^\p{L}])(name|first.?name|last.?name|full.?name|surname|phone|mobile|telephone|tel|e-?mail|address|fayda|national.?id|id.?number|passport|birth|dob|gender|sex|ስም|ስልክ|ኢሜይል|አድራሻ|maqaa|bilbila|teessoo)([^\p{L}]|$)/iu';

    /**
     * @return array{byte_size: int, checksum: string, row_count: ?int, columns: ?list<string>, preview: ?list<list<string>>, personal_columns: ?list<string>}
     */
    public static function inspect(string $path, DistributionFormat $format): array
    {
        $facts = [
            'byte_size' => (int) filesize($path),
            'checksum' => (string) hash_file('sha256', $path),
            'row_count' => null,
            'columns' => null,
            'preview' => null,
            'personal_columns' => null,
        ];
        if (! $format->isTabular()) {
            return $facts;
        }

        try {
            [$columns, $preview, $rows] = match ($format) {
                DistributionFormat::Json => self::json($path),
                default => self::table(self::rows($path, $format)),
            };
        } catch (Throwable) {
            throw new UnreadableFile;
        }

        $personal = array_values(array_filter($columns, fn (string $column): bool => preg_match(self::PERSONAL, str_replace('_', ' ', $column)) === 1));

        return [...$facts, 'row_count' => $rows, 'columns' => $columns, 'preview' => $preview, 'personal_columns' => $personal ?: null];
    }

    /**
     * @param  iterable<array<int, mixed>>  $rows
     * @return array{0: list<string>, 1: list<list<string>>, 2: int}
     */
    private static function table(iterable $rows): array
    {
        $columns = null;
        $preview = [];
        $count = 0;
        foreach ($rows as $row) {
            $cells = array_map(self::cell(...), array_values($row));
            if ($columns === null) {
                $columns = array_slice(array_map(fn (string $c): string => $c !== '' ? $c : '—', $cells), 0, self::PREVIEW_COLUMNS);

                continue;
            }
            if (implode('', $cells) === '') {
                continue;
            }
            $count++;
            if (count($preview) < self::PREVIEW_ROWS) {
                $preview[] = array_slice(array_pad($cells, count($columns), ''), 0, count($columns));
            }
        }
        if ($columns === null) {
            throw new UnreadableFile;
        }

        return [$columns, $preview, $count];
    }

    /**
     * @return Generator<int, array<int, mixed>>
     */
    private static function rows(string $path, DistributionFormat $format): Generator
    {
        $reader = $format === DistributionFormat::Xlsx ? new XlsxReader : new CsvReader;
        $reader->open($path);
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    yield $row->toArray();
                }
                break; // The first sheet only.
            }
        } finally {
            $reader->close();
        }
    }

    /**
     * A JSON file that is a list of records (objects).
     *
     * @return array{0: list<string>, 1: list<list<string>>, 2: int}
     */
    private static function json(string $path): array
    {
        if ((int) filesize($path) > self::MAX_JSON_BYTES) {
            throw new UnreadableFile;
        }
        $data = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
        if (! is_array($data) || ! array_is_list($data) || $data === [] || ! is_array($data[0])) {
            throw new UnreadableFile;
        }

        $columns = [];
        foreach (array_slice($data, 0, 50) as $record) {
            foreach (is_array($record) ? array_keys($record) : [] as $key) {
                if (! in_array((string) $key, $columns, true) && count($columns) < self::PREVIEW_COLUMNS) {
                    $columns[] = (string) $key;
                }
            }
        }
        $preview = [];
        foreach (array_slice($data, 0, self::PREVIEW_ROWS) as $record) {
            $record = is_array($record) ? $record : [];
            $preview[] = array_map(fn (string $column): string => self::cell($record[$column] ?? ''), $columns);
        }

        return [$columns, $preview, count($data)];
    }

    private static function cell(mixed $value): string
    {
        $text = match (true) {
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            $value === null => '',
            default => (string) json_encode($value, JSON_UNESCAPED_UNICODE),
        };
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return mb_strlen($text) > self::CELL_LENGTH ? mb_substr($text, 0, self::CELL_LENGTH - 1).'…' : $text;
    }
}
