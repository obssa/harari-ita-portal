<?php

namespace App\Domain\Academy\Models;

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
 * One language of a course: who it is for, what to know first, what learners will be able to do (one outcome per
 * line) and the syllabus (one module per line).
 *
 * @property string $course_id
 * @property string $title
 * @property ?string $summary
 * @property ?array<string, mixed> $body
 * @property ?string $audience
 * @property ?string $prerequisites
 * @property ?string $outcomes
 * @property ?string $syllabus
 * @property ContentStatus $status
 * @property ?CarbonImmutable $published_at
 * @property ?CarbonImmutable $updated_at
 * @property-read Course $course
 */
class CourseTranslation extends Translation
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
        return [$this->title, $this->summary, $this->audience, $this->outcomes, $this->syllabus, BodyRenderer::toText($this->body)];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('academy')
            ->logOnly(['locale', 'title', 'slug', 'summary', 'audience', 'prerequisites', 'outcomes', 'syllabus', 'status', 'published_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return list<string>
     */
    public function outcomeList(): array
    {
        return self::lines($this->outcomes);
    }

    /**
     * @return list<string>
     */
    public function syllabusList(): array
    {
        return self::lines($this->syllabus);
    }

    /**
     * @return list<string>
     */
    private static function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $text) ?: []), fn (string $line): bool => $line !== ''));
    }
}
