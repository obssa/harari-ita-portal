<?php

namespace App\Domain\OpenData\Support;

use App\Domain\OpenData\Models\Dataset;
use App\Domain\OpenData\Models\DatasetDistribution;
use App\Domain\OpenData\Models\DatasetTranslation;

/**
 * Machine-readable descriptions of a dataset (ADR-0025, ADR-0026): schema.org `Dataset` for search engines on the
 * dataset page, and DCAT-AP (`dcat:Dataset` with its `dcat:Distribution`s) for the catalogue that data portals harvest.
 */
final class DatasetJsonLd
{
    /**
     * @return array<string, mixed>
     */
    public static function schemaOrg(Dataset $dataset, DatasetTranslation $translation): array
    {
        $url = route('datasets.show', ['locale' => $translation->locale, 'slug' => $translation->slug]);

        return array_filter([
            '@type' => 'Dataset',
            '@id' => $url.'#dataset',
            'name' => $translation->title,
            'description' => $translation->summary ?: $translation->title,
            'url' => $url,
            'inLanguage' => $translation->locale,
            'license' => $dataset->licence->url(),
            'isAccessibleForFree' => true,
            'keywords' => $dataset->keywords ?: null,
            'temporalCoverage' => self::period($dataset),
            'spatialCoverage' => $translation->coverage ?: null,
            'dateModified' => $dataset->data_updated_on?->toDateString(),
            'publisher' => ['@id' => url('/').'#organization'],
            'distribution' => $dataset->distributions->filter(fn (DatasetDistribution $d): bool => $d->format->isFile())
                ->map(fn (DatasetDistribution $d): array => [
                    '@type' => 'DataDownload',
                    'encodingFormat' => $d->format->mediaType(),
                    'contentUrl' => route('datasets.download', ['locale' => $translation->locale, 'slug' => $translation->slug, 'distribution' => $d->id]),
                ])->values()->all() ?: null,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * DCAT-AP 3 terms in JSON-LD, one entry of the catalogue.
     *
     * @return array<string, mixed>
     */
    public static function dcat(Dataset $dataset, DatasetTranslation $translation): array
    {
        $url = route('datasets.show', ['locale' => $translation->locale, 'slug' => $translation->slug]);

        return array_filter([
            '@id' => $url.'#dataset',
            '@type' => 'dcat:Dataset',
            'dct:identifier' => $dataset->id,
            'dct:title' => ['@value' => $translation->title, '@language' => $translation->locale],
            'dct:description' => ['@value' => $translation->summary ?: $translation->title, '@language' => $translation->locale],
            'dcat:landingPage' => ['@id' => $url],
            'dct:publisher' => ['@id' => url('/').'#organization'],
            'dct:license' => ['@id' => $dataset->licence->url()],
            'dct:accrualPeriodicity' => ['@id' => $dataset->frequency->euUri()],
            'dct:modified' => $dataset->data_updated_on !== null ? ['@value' => $dataset->data_updated_on->toDateString(), '@type' => 'xsd:date'] : null,
            'dct:issued' => $translation->published_at !== null ? ['@value' => $translation->published_at->toDateString(), '@type' => 'xsd:date'] : null,
            'dcat:keyword' => $dataset->keywords ?: null,
            'dct:temporal' => $dataset->temporal_from !== null || $dataset->temporal_to !== null ? array_filter([
                '@type' => 'dct:PeriodOfTime',
                'dcat:startDate' => $dataset->temporal_from?->toDateString(),
                'dcat:endDate' => $dataset->temporal_to?->toDateString(),
            ]) : null,
            'dct:spatial' => $translation->coverage ? ['@type' => 'dct:Location', 'skos:prefLabel' => $translation->coverage] : null,
            'dcat:contactPoint' => $dataset->contact_email ? ['@type' => 'vcard:Organization', 'vcard:hasEmail' => 'mailto:'.$dataset->contact_email] : null,
            'dcat:distribution' => $dataset->distributions->map(fn (DatasetDistribution $d): array => array_filter([
                '@type' => 'dcat:Distribution',
                'dct:title' => $d->title($translation->locale),
                'dcat:accessURL' => ['@id' => $d->format->isFile() ? $url : (string) $d->url],
                'dcat:downloadURL' => $d->format->isFile() ? ['@id' => route('datasets.download', ['locale' => $translation->locale, 'slug' => $translation->slug, 'distribution' => $d->id])] : null,
                'dct:format' => ['@id' => $d->format->euUri()],
                'dcat:mediaType' => $d->format->mediaType() !== null ? ['@id' => 'https://www.iana.org/assignments/media-types/'.$d->format->mediaType()] : null,
                'dcat:byteSize' => $d->byte_size !== null ? ['@value' => (string) $d->byte_size, '@type' => 'xsd:nonNegativeInteger'] : null,
                'spdx:checksum' => $d->checksum !== null ? ['@type' => 'spdx:Checksum', 'spdx:algorithm' => ['@id' => 'spdx:checksumAlgorithm_sha256'], 'spdx:checksumValue' => $d->checksum] : null,
                'dct:license' => ['@id' => $dataset->licence->url()],
            ], fn (mixed $value): bool => $value !== null))->values()->all(),
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * The JSON-LD context of the DCAT-AP catalogue.
     *
     * @return array<string, string>
     */
    public static function context(): array
    {
        return [
            'dcat' => 'http://www.w3.org/ns/dcat#',
            'dct' => 'http://purl.org/dc/terms/',
            'foaf' => 'http://xmlns.com/foaf/0.1/',
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'spdx' => 'http://spdx.org/rdf/terms#',
            'vcard' => 'http://www.w3.org/2006/vcard/ns#',
            'xsd' => 'http://www.w3.org/2001/XMLSchema#',
        ];
    }

    private static function period(Dataset $dataset): ?string
    {
        if ($dataset->temporal_from === null && $dataset->temporal_to === null) {
            return null;
        }

        return ($dataset->temporal_from?->toDateString() ?? '..').'/'.($dataset->temporal_to?->toDateString() ?? '..');
    }
}
