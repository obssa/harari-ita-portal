<?php

namespace App\Domain\Innovation\Models;

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
 * One language of a programme: who it is for, what it gives (one benefit per line) and its timeline (one stage
 * per line).
 *
 * @property string $programme_id
 * @property string $title
 * @property ?string $summary
 * @property ?array<string, mixed> $body
 * @property ?string $eligibility
 * @property ?string $benefits
 * @property ?string $timeline
 * @property ContentStatus $status
 * @property ?CarbonImmutable $published_at
 * @property-read Programme $programme
 */
class ProgrammeTranslation extends Translation
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
        return [$this->title, $this->summary, $this->eligibility, $this->benefits, BodyRenderer::toText($this->body)];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('innovation')
            ->logOnly(['locale', 'title', 'slug', 'summary', 'eligibility', 'benefits', 'timeline', 'status', 'published_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Programme, $this>
     */
    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    /**
     * @return list<string>
     */
    public function benefitList(): array
    {
        return self::lines($this->benefits);
    }

    /**
     * @return list<string>
     */
    public function timelineList(): array
    {
        return self::lines($this->timeline);
    }

    /**
     * @return list<string>
     */
    private static function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $text) ?: []), fn (string $line): bool => $line !== ''));
    }
}
