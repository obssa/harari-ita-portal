<?php

namespace App\Domain\Academy\Models;

use App\Domain\Status\Support\LocalisedText;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One meeting of a cohort (ADR-0028): when it starts and ends and, optionally, what it covers in each language.
 * Attendance is marked per session.
 *
 * @property int $id
 * @property int $course_cohort_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property ?array<string, string> $titles
 * @property-read CourseCohort $cohort
 * @property-read Collection<int, Attendance> $attendances
 */
class CourseSession extends Model
{
    use LogsActivity;

    protected $fillable = ['starts_at', 'ends_at', 'titles'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'titles' => 'array'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('academy')->logOnly(['starts_at', 'ends_at', 'titles'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<CourseCohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(CourseCohort::class, 'course_cohort_id');
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function title(?string $locale = null): string
    {
        return LocalisedText::pick($this->titles, $locale);
    }

    public function hasStarted(?CarbonImmutable $now = null): bool
    {
        return $this->starts_at <= ($now ?? CarbonImmutable::now());
    }
}
