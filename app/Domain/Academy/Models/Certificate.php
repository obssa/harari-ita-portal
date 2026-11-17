<?php

namespace App\Domain\Academy\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A certificate of completion (ADR-0030). Its code `CRT-XXXX-XXXX` is random, so certificates cannot be listed by
 * counting; its signature covers the code, the course, the cohort, the holder's initials and the issue time, so a
 * row changed outside the portal no longer verifies. The holder's full name is encrypted and printed on the
 * certificate only; verification shows the initials.
 *
 * @property string $id
 * @property string $enrolment_id
 * @property string $course_id
 * @property int $course_cohort_id
 * @property string $code
 * @property ?string $holder_name
 * @property ?string $holder_initials
 * @property string $signature
 * @property CarbonImmutable $issued_at
 * @property ?int $issued_by
 * @property ?CarbonImmutable $revoked_at
 * @property ?int $revoked_by
 * @property ?string $revoke_reason
 * @property-read Enrolment $enrolment
 * @property-read Course $course
 * @property-read CourseCohort $cohort
 * @property-read ?User $issuer
 */
class Certificate extends Model
{
    use BelongsToSite;
    use HasUlids;
    use LogsActivity;

    public const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public const PREFIX = 'CRT';

    public const PATTERN = 'CRT-[2-9A-HJ-NP-Z]{4}-[2-9A-HJ-NP-Z]{4}';

    protected $fillable = ['enrolment_id', 'course_id', 'course_cohort_id', 'code', 'holder_name', 'holder_initials', 'signature', 'issued_at', 'issued_by'];

    protected $hidden = ['holder_name'];

    protected function casts(): array
    {
        return [
            'holder_name' => 'encrypted',
            'issued_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('academy')->logOnly(['code', 'issued_at', 'issued_by', 'revoked_at', 'revoked_by', 'revoke_reason'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Enrolment, $this>
     */
    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(Enrolment::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class)->withTrashed();
    }

    /**
     * @return BelongsTo<CourseCohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(CourseCohort::class, 'course_cohort_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public static function newCode(): string
    {
        do {
            $plain = '';
            for ($i = 0; $i < 8; $i++) {
                $plain .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $code = self::PREFIX.'-'.substr($plain, 0, 4).'-'.substr($plain, 4);
        } while (self::query()->withoutGlobalScopes()->where('code', $code)->exists());

        return $code;
    }

    /** A typed code in any case, with or without dashes and the prefix; empty when it cannot be one. */
    public static function normaliseCode(string $typed): string
    {
        $plain = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $typed));
        if (str_starts_with($plain, self::PREFIX)) {
            $plain = substr($plain, strlen(self::PREFIX));
        }

        if (strlen($plain) !== 8 || strspn($plain, self::ALPHABET) !== 8) {
            return '';
        }

        return self::PREFIX.'-'.substr($plain, 0, 4).'-'.substr($plain, 4);
    }

    public function computeSignature(): string
    {
        return hash_hmac('sha256', implode('|', [
            'certificate', $this->code, $this->course_id, $this->course_cohort_id, (string) $this->holder_initials, $this->issued_at->utc()->format('Y-m-d\TH:i:s'),
        ]), (string) config('app.key'));
    }

    public function hasValidSignature(): bool
    {
        return hash_equals($this->computeSignature(), $this->signature);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isRevoked() && $this->hasValidSignature();
    }

    public function verifyUrl(?string $locale = null): string
    {
        return route('certificates.verify.show', ['locale' => $locale ?? app()->getLocale(), 'code' => $this->code]);
    }
}
