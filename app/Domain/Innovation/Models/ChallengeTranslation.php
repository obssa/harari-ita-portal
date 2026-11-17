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
 * One language of a challenge: the brief, the prizes (one per line) and the rules.
 *
 * @property string $challenge_id
 * @property string $title
 * @property ?string $summary
 * @property ?array<string, mixed> $body
 * @property ?string $prizes
 * @property ?string $rules
 * @property ContentStatus $status
 * @property ?CarbonImmutable $published_at
 * @property-read Challenge $challenge
 */
class ChallengeTranslation extends Translation
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
        return [$this->title, $this->summary, $this->prizes, BodyRenderer::toText($this->body)];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('innovation')
            ->logOnly(['locale', 'title', 'slug', 'summary', 'prizes', 'rules', 'status', 'published_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Challenge, $this>
     */
    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    /**
     * @return list<string>
     */
    public function prizeList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $this->prizes) ?: []), fn (string $line): bool => $line !== ''));
    }
}
