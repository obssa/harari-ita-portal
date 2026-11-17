<?php

namespace App\Domain\OpenData\Models;

use App\Domain\Content\Concerns\PublishedPerLanguage;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\Translation;
use App\Domain\Content\Support\BodyRenderer;
use App\Domain\Search\Concerns\SearchableTranslation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One language of a dataset: its title, summary, description, how the data was collected, and the area it covers.
 *
 * @property string $dataset_id
 * @property string $title
 * @property ?string $summary
 * @property ?array<string, mixed> $body
 * @property ?string $methodology
 * @property ?string $coverage
 * @property ContentStatus $status
 * @property ?CarbonImmutable $published_at
 * @property ?CarbonImmutable $updated_at
 * @property-read Dataset $dataset
 */
class DatasetTranslation extends Translation
{
    use LogsActivity;
    use PublishedPerLanguage;
    use SearchableTranslation;

    protected string $sluggable = 'title';

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return ['body' => 'array', 'status' => ContentStatus::class, 'published_at' => 'immutable_datetime'];
    }

    /**
     * @return list<?string>
     */
    public function searchableParts(): array
    {
        return [$this->title, $this->summary, $this->coverage, BodyRenderer::toText($this->body), implode(' ', $this->dataset->keywords ?? [])];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('opendata')
            ->logOnly(['locale', 'title', 'slug', 'summary', 'methodology', 'coverage', 'status', 'published_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Dataset, $this>
     */
    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }
}
