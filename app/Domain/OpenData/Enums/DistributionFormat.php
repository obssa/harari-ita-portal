<?php

namespace App\Domain\OpenData\Enums;

/**
 * The forms a dataset is published in (ADR-0025): a file from the media library, or a link to an API. Each file format
 * lists the content types the uploaded file may have (as detected from its bytes) and its IANA media type for DCAT-AP.
 */
enum DistributionFormat: string
{
    case Csv = 'csv';
    case Json = 'json';
    case Xlsx = 'xlsx';
    case Pdf = 'pdf';
    case Api = 'api';

    public function label(): string
    {
        return __("opendata.format.{$this->value}");
    }

    /** Short code for chips and the EU file-type vocabulary. */
    public function code(): string
    {
        return strtoupper($this->value);
    }

    public function isFile(): bool
    {
        return $this !== self::Api;
    }

    /** Machine-readable data that the portal can preview. */
    public function isTabular(): bool
    {
        return in_array($this, [self::Csv, self::Json, self::Xlsx], true);
    }

    public function mediaType(): ?string
    {
        return match ($this) {
            self::Csv => 'text/csv',
            self::Json => 'application/json',
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Pdf => 'application/pdf',
            self::Api => null,
        };
    }

    /**
     * Content types an uploaded file of this format may be detected as. CSV and JSON are plain text to most detectors.
     *
     * @return list<string>
     */
    public function acceptedMimes(): array
    {
        return match ($this) {
            self::Csv => ['text/csv', 'text/plain'],
            self::Json => ['application/json', 'text/plain'],
            self::Xlsx => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            self::Pdf => ['application/pdf'],
            self::Api => [],
        };
    }

    public function euUri(): string
    {
        return 'http://publications.europa.eu/resource/authority/file-type/'.($this === self::Api ? 'REST' : $this->code());
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $format) {
            $options[$format->value] = $format->label();
        }

        return $options;
    }
}
