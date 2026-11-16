<?php

namespace App\Domain\Innovation\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use App\Domain\Innovation\Enums\ApplicationStage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * An application to a programme cohort (ADR-0021) with a random reference `APP-XXXX-XXXX`. The applicant's details
 * and answers are encrypted; the audit log records the handling only. Scores are 1 to 5 per rubric criterion; the
 * score is their average.
 *
 * @property string $id
 * @property string $reference
 * @property string $programme_id
 * @property int $cohort_id
 * @property ?string $startup_id
 * @property string $locale
 * @property ?string $applicant_name
 * @property ?string $applicant_email
 * @property ?string $applicant_phone
 * @property ?string $email_hash
 * @property ?string $phone_hash
 * @property ?string $team_name
 * @property ?array<string, string> $answers
 * @property ApplicationStage $stage
 * @property ?array<string, int> $scores
 * @property ?string $score
 * @property ?int $scored_by
 * @property ?string $decision_note
 * @property ?CarbonImmutable $decided_at
 * @property ?CarbonImmutable $consented_at
 * @property ?CarbonImmutable $erased_at
 * @property CarbonImmutable $created_at
 * @property-read Programme $programme
 * @property-read Cohort $cohort
 * @property-read ?User $scorer
 */
class Application extends Model
{
    use BelongsToSite;
    use HasUlids;
    use LogsActivity;

    public const REFERENCE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public const REFERENCE_PREFIX = 'APP';

    public const REFERENCE_PATTERN = 'APP-[2-9A-HJ-NP-Z]{4}-[2-9A-HJ-NP-Z]{4}';

    /** The rubric (PLAN.md §5): each criterion scored from 1 to 5. */
    public const CRITERIA = ['problem', 'solution', 'team', 'impact', 'feasibility'];

    protected $fillable = [
        'reference', 'programme_id', 'cohort_id', 'startup_id', 'locale', 'applicant_name', 'applicant_email',
        'applicant_phone', 'email_hash', 'phone_hash', 'team_name', 'answers', 'stage', 'consented_at',
    ];

    protected $hidden = ['applicant_name', 'applicant_email', 'applicant_phone', 'answers'];

    protected $attributes = ['stage' => 'received'];

    protected function casts(): array
    {
        return [
            'applicant_name' => 'encrypted',
            'applicant_email' => 'encrypted',
            'applicant_phone' => 'encrypted',
            'answers' => 'encrypted:array',
            'stage' => ApplicationStage::class,
            'scores' => 'array',
            'decided_at' => 'immutable_datetime',
            'consented_at' => 'immutable_datetime',
            'erased_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('innovation')
            ->logOnly(['stage', 'score', 'scored_by', 'decided_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Programme, $this>
     */
    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Cohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function scorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scored_by');
    }

    public static function newReference(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= self::REFERENCE_ALPHABET[random_int(0, strlen(self::REFERENCE_ALPHABET) - 1)];
            }
            $reference = self::REFERENCE_PREFIX.'-'.substr($code, 0, 4).'-'.substr($code, 4);
        } while (self::query()->withoutGlobalScopes()->where('reference', $reference)->exists());

        return $reference;
    }

    public static function normaliseReference(string $typed): string
    {
        $plain = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $typed));
        if (str_starts_with($plain, self::REFERENCE_PREFIX)) {
            $plain = substr($plain, strlen(self::REFERENCE_PREFIX));
        }

        if (strlen($plain) !== 8 || strspn($plain, self::REFERENCE_ALPHABET) !== 8) {
            return '';
        }

        return self::REFERENCE_PREFIX.'-'.substr($plain, 0, 4).'-'.substr($plain, 4);
    }
}
