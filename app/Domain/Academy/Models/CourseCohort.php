<?php

namespace App\Domain\Academy\Models;

use App\Domain\Academy\Enums\CourseMode;
use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Organisation\Models\Office;
use App\Domain\Status\Support\LocalisedText;
use App\Services\Calendar\EthiopianCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One run of a course (ADR-0028): its mode and venue, the dates it runs, when enrolment opens and closes, the places,
 * whether a waiting list is kept, its sessions and instructors. Enrolment closes when the cohort starts unless a time
 * is set; with no opening time it is open as soon as the course is published.
 *
 * @property int $id
 * @property string $course_id
 * @property string $name
 * @property CourseMode $mode
 * @property ?string $office_id
 * @property ?array<string, string> $venue_notes
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 * @property ?CarbonImmutable $enrol_opens_at
 * @property ?CarbonImmutable $enrol_closes_at
 * @property ?int $capacity
 * @property bool $waitlist
 * @property ?CarbonImmutable $cancelled_at
 * @property-read Course $course
 * @property-read ?Office $office
 * @property-read Collection<int, CourseSession> $sessions
 * @property-read Collection<int, Instructor> $instructors
 * @property-read Collection<int, Enrolment> $enrolments
 * @property-read Collection<int, Certificate> $certificates
 */
class CourseCohort extends Model
{
    use LogsActivity;

    public const STATE_CANCELLED = 'cancelled';

    /** Enrolment has not opened yet. */
    public const STATE_SOON = 'soon';

    public const STATE_OPEN = 'open';

    /** Full, but learners can join the waiting list. */
    public const STATE_WAITLIST = 'waitlist';

    public const STATE_FULL = 'full';

    public const STATE_CLOSED = 'closed';

    protected $fillable = ['name', 'mode', 'office_id', 'venue_notes', 'starts_on', 'ends_on', 'enrol_opens_at', 'enrol_closes_at', 'capacity', 'waitlist'];

    protected $attributes = ['waitlist' => true];

    protected function casts(): array
    {
        return [
            'mode' => CourseMode::class,
            'venue_notes' => 'array',
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'enrol_opens_at' => 'immutable_datetime',
            'enrol_closes_at' => 'immutable_datetime',
            'capacity' => 'integer',
            'waitlist' => 'boolean',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('academy')
            ->logOnly(['name', 'mode', 'office_id', 'starts_on', 'ends_on', 'enrol_opens_at', 'enrol_closes_at', 'capacity', 'waitlist', 'cancelled_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * @return HasMany<CourseSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(CourseSession::class)->orderBy('starts_at')->orderBy('id');
    }

    /**
     * @return BelongsToMany<Instructor, $this>
     */
    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class, 'course_cohort_instructor')->orderBy('name');
    }

    /**
     * @return HasMany<Enrolment, $this>
     */
    public function enrolments(): HasMany
    {
        return $this->hasMany(Enrolment::class);
    }

    /**
     * @return HasMany<Certificate, $this>
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function hasEnded(?CarbonImmutable $today = null): bool
    {
        return $this->ends_on < ($today ?? CarbonImmutable::today());
    }

    public function venueNote(?string $locale = null): string
    {
        return LocalisedText::pick($this->venue_notes, $locale);
    }

    /** When enrolment stops: the chosen time, otherwise the start of the first day in Ethiopia. */
    public function enrolClosesAt(): CarbonImmutable
    {
        return $this->enrol_closes_at ?? CarbonImmutable::parse($this->starts_on->toDateString(), EthiopianCalendar::timezone());
    }

    public function enrolledCount(): int
    {
        return $this->enrolments()->where('status', EnrolmentStatus::Enrolled->value)->count();
    }

    /** Places still free, or null without a limit. */
    public function placesLeft(): ?int
    {
        return $this->capacity === null ? null : max(0, $this->capacity - $this->enrolledCount());
    }

    public function enrolmentState(?CarbonImmutable $now = null): string
    {
        $now ??= CarbonImmutable::now();

        return match (true) {
            $this->isCancelled() => self::STATE_CANCELLED,
            $this->enrolClosesAt() <= $now => self::STATE_CLOSED,
            $this->enrol_opens_at !== null && $this->enrol_opens_at > $now => self::STATE_SOON,
            $this->capacity === null || $this->enrolledCount() < $this->capacity => self::STATE_OPEN,
            $this->waitlist => self::STATE_WAITLIST,
            default => self::STATE_FULL,
        };
    }

    public function acceptsEnrolment(?CarbonImmutable $now = null): bool
    {
        return in_array($this->enrolmentState($now), [self::STATE_OPEN, self::STATE_WAITLIST], true);
    }

    /**
     * Cohorts not cancelled that have not ended.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeUpcoming(Builder $query, ?CarbonImmutable $today = null): Builder
    {
        return $query->whereNull('cancelled_at')->whereDate('ends_on', '>=', ($today ?? CarbonImmutable::today())->toDateString());
    }

    /** The name shown in the admin: the course and the cohort. */
    public function label(?string $locale = null): string
    {
        return trim($this->loadMissing('course.translations')->course->title($locale).' · '.$this->name, ' ·');
    }
}
