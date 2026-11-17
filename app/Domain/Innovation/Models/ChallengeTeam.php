<?php

namespace App\Domain\Innovation\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A team in a challenge (ADR-0022), with its one submission (a title, a description and links). The team's
 * reference `TEM-XXXX-XXXX` plus the contact's email or phone opens its page. The contact and the members are
 * encrypted; the team name is public only in published results, and only when the team agreed.
 *
 * @property string $id
 * @property string $challenge_id
 * @property string $reference
 * @property string $locale
 * @property string $name
 * @property ?string $contact_name
 * @property ?string $contact_email
 * @property ?string $contact_phone
 * @property ?string $email_hash
 * @property ?string $phone_hash
 * @property ?string $members
 * @property ?string $submission_title
 * @property ?string $submission_description
 * @property ?list<string> $submission_links
 * @property ?CarbonImmutable $submitted_at
 * @property ?string $score
 * @property ?int $rank
 * @property ?string $award
 * @property bool $show_in_results
 * @property ?CarbonImmutable $consented_at
 * @property ?CarbonImmutable $erased_at
 * @property CarbonImmutable $created_at
 * @property-read Challenge $challenge
 * @property-read Collection<int, JudgeAssignment> $assignments
 */
class ChallengeTeam extends Model
{
    use BelongsToSite;
    use HasUlids;
    use LogsActivity;

    public const REFERENCE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public const REFERENCE_PREFIX = 'TEM';

    public const REFERENCE_PATTERN = 'TEM-[2-9A-HJ-NP-Z]{4}-[2-9A-HJ-NP-Z]{4}';

    protected $fillable = [
        'challenge_id', 'reference', 'locale', 'name', 'contact_name', 'contact_email', 'contact_phone', 'email_hash',
        'phone_hash', 'members', 'show_in_results', 'consented_at',
    ];

    protected $hidden = ['contact_name', 'contact_email', 'contact_phone', 'members'];

    protected $attributes = ['show_in_results' => false];

    protected function casts(): array
    {
        return [
            'contact_name' => 'encrypted',
            'contact_email' => 'encrypted',
            'contact_phone' => 'encrypted',
            'members' => 'encrypted',
            'submission_links' => 'array',
            'submitted_at' => 'immutable_datetime',
            'rank' => 'integer',
            'show_in_results' => 'boolean',
            'consented_at' => 'immutable_datetime',
            'erased_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('innovation')
            ->logOnly(['name', 'submitted_at', 'score', 'rank', 'award', 'show_in_results'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Challenge, $this>
     */
    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class)->withTrashed();
    }

    /**
     * @return HasMany<JudgeAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(JudgeAssignment::class);
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
