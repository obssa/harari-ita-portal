<?php

namespace App\Domain\Opportunities\Models;

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
 * One language of an opportunity: the summary, the description, who may apply (one requirement per line), how to
 * apply, and the outcome once it is decided (for example the award notice).
 *
 * @property string $opportunity_id
 * @property string $title
 * @property ?string $summary
 * @property ?array<string, mixed> $body
 * @property ?string $eligibility
 * @property ?string $how_to_apply
 * @property ?string $outcome
 * @property ContentStatus $status
 * @property ?CarbonImmutable $published_at
 * @property ?CarbonImmutable $updated_at
 * @property-read Opportunity $opportunity
 */
class OpportunityTranslation extends Translation
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
        return [$this->title, $this->summary, $this->eligibility, $this->how_to_apply, $this->outcome, BodyRenderer::toText($this->body)];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('opportunities')
            ->logOnly(['locale', 'title', 'slug', 'summary', 'eligibility', 'how_to_apply', 'outcome', 'status', 'published_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Opportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /**
     * @return list<string>
     */
    public function eligibilityList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $this->eligibility) ?: []), fn (string $line): bool => $line !== ''));
    }
}
