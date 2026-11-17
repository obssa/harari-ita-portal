<?php

namespace App\Domain\OpenData\Models;

use App\Domain\Media\Models\Media;
use App\Domain\OpenData\Enums\DistributionFormat;
use App\Domain\Status\Support\LocalisedText;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One form a dataset is published in (ADR-0025): a file from the media library, or a link to an API. For a file the
 * size, SHA-256 checksum, rows, column names and a preview of the first rows are worked out when it is saved; column
 * names that look like personal data are kept as a warning for the Data Steward.
 *
 * @property int $id
 * @property string $dataset_id
 * @property DistributionFormat $format
 * @property ?array<string, string> $titles
 * @property ?int $media_id
 * @property ?string $url
 * @property ?int $byte_size
 * @property ?string $checksum
 * @property ?int $row_count
 * @property ?list<string> $columns
 * @property ?list<list<string>> $preview
 * @property ?list<string> $personal_columns
 * @property int $download_count
 * @property int $sort
 * @property ?CarbonImmutable $updated_at
 * @property-read Dataset $dataset
 * @property-read ?Media $media
 */
class DatasetDistribution extends Model
{
    use LogsActivity;

    protected $fillable = ['format', 'titles', 'media_id', 'url', 'byte_size', 'checksum', 'row_count', 'columns', 'preview', 'personal_columns', 'sort'];

    protected $attributes = ['download_count' => 0, 'sort' => 0];

    protected function casts(): array
    {
        return [
            'format' => DistributionFormat::class,
            'titles' => 'array',
            'byte_size' => 'integer',
            'row_count' => 'integer',
            'columns' => 'array',
            'preview' => 'array',
            'personal_columns' => 'array',
            'download_count' => 'integer',
            'sort' => 'integer',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('opendata')->logOnly(['format', 'titles', 'media_id', 'url', 'checksum', 'sort'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Dataset, $this>
     */
    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return HasMany<DatasetDownload, $this>
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(DatasetDownload::class);
    }

    /** The name shown for this file: its own title, else the format. */
    public function title(?string $locale = null): string
    {
        return LocalisedText::pick($this->titles, $locale) ?: $this->format->label();
    }

    /** The file name a download is saved under. */
    public function fileName(string $slug): string
    {
        return $slug.($this->sort > 0 ? '-'.($this->sort + 1) : '').'.'.$this->format->value;
    }
}
