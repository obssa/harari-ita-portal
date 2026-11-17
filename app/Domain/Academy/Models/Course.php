<?php

namespace App\Domain\Academy\Models;

use App\Domain\Academy\Enums\CourseLevel;
use App\Domain\Academy\Enums\CourseMode;
use App\Domain\Content\Concerns\BelongsToSite;
use App\Domain\Content\Concerns\HasTranslations;
use App\Domain\Content\Concerns\TracksEditors;
use App\Domain\Content\Contracts\TranslatableModel;
use App\Domain\Media\Models\Media;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Status\Support\LocalisedText;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A course of the Academy (ADR-0028), run by the Agency or with a partner: its level, usual mode, length, the
 * languages it is taught in, the attendance a certificate needs and who signs the certificate. Each run is a cohort.
 *
 * @property string $id
 * @property ?string $pillar_id
 * @property CourseLevel $level
 * @property CourseMode $mode
 * @property ?int $duration_hours
 * @property ?list<string> $languages
 * @property ?string $partner
 * @property ?int $cover_image_id
 * @property int $attendance_required percent of sessions
 * @property ?string $signatory_name
 * @property ?array<string, string> $signatory_titles
 * @property bool $featured
 * @property int $sort
 * @property ?CarbonImmutable $updated_at
 * @property-read ?Pillar $pillar
 * @property-read ?Media $coverImage
 * @property-read Collection<int, CourseCohort> $cohorts
 */
class Course extends Model implements TranslatableModel
{
    use BelongsToSite;

    /** @use HasTranslations<CourseTranslation> */
    use HasTranslations;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;
    use TracksEditors;

    public const DEFAULT_ATTENDANCE = 80;

    protected $fillable = [
        'pillar_id', 'level', 'mode', 'duration_hours', 'languages', 'partner', 'cover_image_id',
        'attendance_required', 'signatory_name', 'signatory_titles', 'featured', 'sort',
    ];

    protected $attributes = ['level' => 'beginner', 'mode' => 'in_person', 'attendance_required' => self::DEFAULT_ATTENDANCE, 'featured' => false, 'sort' => 0];

    protected function casts(): array
    {
        return [
            'level' => CourseLevel::class,
            'mode' => CourseMode::class,
            'duration_hours' => 'integer',
            'languages' => 'array',
            'attendance_required' => 'integer',
            'signatory_titles' => 'array',
            'featured' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public static function translationModel(): string
    {
        return CourseTranslation::class;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('academy')
            ->logOnly(['pillar_id', 'level', 'mode', 'duration_hours', 'languages', 'partner', 'cover_image_id', 'attendance_required', 'signatory_name', 'featured', 'sort'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Pillar, $this>
     */
    public function pillar(): BelongsTo
    {
        return $this->belongsTo(Pillar::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_image_id');
    }

    /**
     * @return HasMany<CourseCohort, $this>
     */
    public function cohorts(): HasMany
    {
        return $this->hasMany(CourseCohort::class)->orderBy('starts_on')->orderBy('id');
    }

    public function title(?string $locale = null): string
    {
        return (string) $this->translationOrAny($locale)?->getAttribute('title');
    }

    public function signatoryTitle(?string $locale = null): string
    {
        return LocalisedText::pick($this->signatory_titles, $locale);
    }

    /**
     * The cohorts not cancelled that have not ended, soonest first (the relation must be loaded).
     *
     * @return Collection<int, CourseCohort>
     */
    public function upcomingCohorts(?CarbonImmutable $today = null): Collection
    {
        $today ??= CarbonImmutable::today();

        return $this->cohorts->filter(fn (CourseCohort $cohort): bool => ! $cohort->isCancelled() && $cohort->ends_on >= $today)->values();
    }

    /** The next cohort a learner could join or wait for. */
    public function nextCohort(?CarbonImmutable $today = null): ?CourseCohort
    {
        return $this->upcomingCohorts($today)->first();
    }
}
