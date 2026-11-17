<?php

namespace App\Domain\Academy\Models;

use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Events\Models\EventRegistration;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A learner's place in a cohort (ADR-0029). The name, phone, email and the private link's code are encrypted and
 * found by keyed hashes (the same as event registrations, so the privacy search finds both); the audit log records
 * status changes only.
 *
 * @property string $id
 * @property int $course_cohort_id
 * @property string $locale
 * @property ?string $name
 * @property ?string $phone
 * @property ?string $email
 * @property ?string $phone_hash
 * @property ?string $email_hash
 * @property EnrolmentStatus $status
 * @property ?string $token
 * @property string $token_hash
 * @property ?CarbonImmutable $consented_at
 * @property ?CarbonImmutable $promoted_at
 * @property ?CarbonImmutable $reminded_at
 * @property ?CarbonImmutable $cancelled_at
 * @property ?CarbonImmutable $erased_at
 * @property CarbonImmutable $created_at
 * @property-read CourseCohort $cohort
 * @property-read Collection<int, Attendance> $attendances
 * @property-read ?Certificate $certificate
 * @property-read Collection<int, Certificate> $certificates
 */
class Enrolment extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['locale', 'name', 'phone', 'email', 'phone_hash', 'email_hash', 'status', 'token', 'token_hash', 'consented_at'];

    protected $hidden = ['name', 'phone', 'email', 'token', 'token_hash', 'phone_hash', 'email_hash'];

    protected function casts(): array
    {
        return [
            'name' => 'encrypted',
            'phone' => 'encrypted',
            'email' => 'encrypted',
            'token' => 'encrypted',
            'status' => EnrolmentStatus::class,
            'consented_at' => 'immutable_datetime',
            'promoted_at' => 'immutable_datetime',
            'reminded_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'erased_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('academy')->logOnly(['status', 'cancelled_at', 'erased_at'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public static function hash(string $value): string
    {
        return EventRegistration::hash($value);
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

    /**
     * The certificate that stands: issued and not revoked. A revoked one stays in `certificates` for the record.
     *
     * @return HasOne<Certificate, $this>
     */
    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class)->whereNull('revoked_at');
    }

    /**
     * @return HasMany<Certificate, $this>
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', EnrolmentStatus::Cancelled->value);
    }

    public static function findByToken(string $token): ?self
    {
        return $token === '' ? null : self::query()->where('token_hash', self::hash($token))->whereNull('erased_at')->first();
    }

    /** Learners ahead of this one on the waiting list, plus one. */
    public function waitlistPosition(): ?int
    {
        if ($this->status !== EnrolmentStatus::Waitlisted) {
            return null;
        }

        return self::query()
            ->where('course_cohort_id', $this->course_cohort_id)
            ->where('status', EnrolmentStatus::Waitlisted->value)
            ->where(fn (Builder $q) => $q->where('created_at', '<', $this->created_at)
                ->orWhere(fn (Builder $same) => $same->where('created_at', $this->created_at)->where('id', '<', $this->id)))
            ->count() + 1;
    }
}
