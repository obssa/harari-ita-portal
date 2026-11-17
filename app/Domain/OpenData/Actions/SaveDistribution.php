<?php

namespace App\Domain\OpenData\Actions;

use App\Domain\Media\Actions\SyncMediaUsage;
use App\Domain\Media\Enums\MediaKind;
use App\Domain\Media\Models\Media;
use App\Domain\OpenData\Enums\DistributionFormat;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\OpenData\Models\DatasetDistribution;
use App\Domain\OpenData\Support\DatasetFile;
use App\Domain\OpenData\Support\UnreadableFile;
use App\Domain\Status\Support\LocalisedText;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Adds or changes a dataset's distribution (ADR-0025). A file comes from the media library and must match its format
 * (a CSV that reads as a table, a JSON list of records, an XLSX workbook, a PDF); its facts and preview are worked out
 * here. An API is an https link. A new or changed file moves the dataset's "data updated" date to today.
 */
final class SaveDistribution
{
    public const MAX_PER_DATASET = 10;

    public function __construct(private readonly SyncMediaUsage $usage) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Dataset $dataset, DatasetDistribution $distribution, array $data, User $actor): DatasetDistribution
    {
        if (! $actor->can('update', $dataset)) {
            throw new AuthorizationException;
        }

        $format = DistributionFormat::tryFrom((string) ($data['format'] ?? ''));
        if ($format === null) {
            throw ValidationException::withMessages(['format' => __('opendata.validation.format')]);
        }
        if (! $distribution->exists && $dataset->distributions()->count() >= self::MAX_PER_DATASET) {
            throw ValidationException::withMessages(['format' => __('opendata.validation.max_distributions', ['max' => self::MAX_PER_DATASET])]);
        }

        $facts = ['byte_size' => null, 'checksum' => null, 'row_count' => null, 'columns' => null, 'preview' => null, 'personal_columns' => null];
        $mediaId = null;
        $url = null;
        if ($format->isFile()) {
            $media = filled($data['media_id'] ?? null) ? Media::query()->whereKey((int) $data['media_id'])->where('kind', MediaKind::Document->value)->first() : null;
            if ($media === null) {
                throw ValidationException::withMessages(['media_id' => __('opendata.validation.file')]);
            }
            if (! in_array(strtolower((string) $media->mime_type), $format->acceptedMimes(), true)) {
                throw ValidationException::withMessages(['media_id' => __('opendata.validation.file_format', ['format' => $format->code()])]);
            }
            $mediaId = $media->id;
            if ($distribution->media_id !== $mediaId || $distribution->checksum === null) {
                try {
                    $facts = DatasetFile::inspect($media->getPath(), $format);
                } catch (UnreadableFile) {
                    throw ValidationException::withMessages(['media_id' => __('opendata.validation.unreadable', ['format' => $format->code()])]);
                }
            } else {
                $facts = $distribution->only(array_keys($facts));
            }
        } else {
            $url = trim((string) ($data['url'] ?? ''));
            if (! str_starts_with($url, 'https://') || filter_var($url, FILTER_VALIDATE_URL) === false || mb_strlen($url) > 500) {
                throw ValidationException::withMessages(['url' => __('opendata.validation.url')]);
            }
        }

        $changed = ! $distribution->exists || $distribution->media_id !== $mediaId || $distribution->url !== $url;
        $distribution->fill([
            ...$facts,
            'format' => $format,
            'titles' => LocalisedText::clean($data['titles'] ?? [], 120) ?: null,
            'media_id' => $mediaId,
            'url' => $url,
            'sort' => $distribution->exists ? $distribution->sort : $dataset->distributions()->count(),
        ]);
        $distribution->dataset()->associate($dataset);
        $distribution->save();

        if ($changed) {
            $dataset->data_updated_on = CarbonImmutable::today();
        }
        $dataset->save();
        $dataset->touch();
        $this->usage->handle($dataset, 'distributions', $dataset->distributions()->whereNotNull('media_id')->pluck('media_id')->map(fn (mixed $id): int => (int) $id)->all());

        return $distribution;
    }

    public function remove(DatasetDistribution $distribution, User $actor): void
    {
        $dataset = $distribution->dataset;
        if (! $actor->can('update', $dataset)) {
            throw new AuthorizationException;
        }
        $distribution->delete();
        $dataset->touch();
        $this->usage->handle($dataset, 'distributions', $dataset->distributions()->whereNotNull('media_id')->pluck('media_id')->map(fn (mixed $id): int => (int) $id)->all());
    }
}
