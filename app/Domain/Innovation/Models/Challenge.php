<?php

namespace App\Domain\Innovation\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use App\Domain\Content\Concerns\HasTranslations;
use App\Domain\Content\Concerns\TracksEditors;
use App\Domain\Content\Contracts\TranslatableModel;
use App\Domain\Media\Models\Media;
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
 * A challenge or hackathon (ADR-0022). Its phases follow from four dates: registration opens and closes, submissions
 * close, then judging until the results are published. The rubric has criteria with weights.
 *
 * @property string $id
 * @property ?int $cover_image_id
 * @property CarbonImmutable $registration_opens_at
 * @property CarbonImmutable $registration_closes_at
 * @property CarbonImmutable $submission_closes_at
 * @property ?CarbonImmutable $results_on
 * @property ?CarbonImmutable $results_published_at
 * @property int $max_team_size
 * @property ?list<array<string, mixed>> $criteria read through criteriaIn()
 * @property-read ?Media $coverImage
 * @property-read Collection<int, ChallengeTeam> $teams
 */
class Challenge extends Model implements TranslatableModel
{
    use BelongsToSite;

    /** @use HasTranslations<ChallengeTranslation> */
    use HasTranslations;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;
    use TracksEditors;

    public const PHASE_UPCOMING = 'upcoming';

    public const PHASE_REGISTRATION = 'registration';

    public const PHASE_BUILDING = 'building';

    public const PHASE_JUDGING = 'judging';

    public const PHASE_RESULTS = 'results';

    protected $fillable = ['cover_image_id', 'registration_opens_at', 'registration_closes_at', 'submission_closes_at', 'results_on', 'max_team_size', 'criteria'];

    protected $attributes = ['max_team_size' => 5];

    protected function casts(): array
    {
        return [
            'registration_opens_at' => 'immutable_datetime',
            'registration_closes_at' => 'immutable_datetime',
            'submission_closes_at' => 'immutable_datetime',
            'results_on' => 'immutable_date',
            'results_published_at' => 'immutable_datetime',
            'max_team_size' => 'integer',
            'criteria' => 'array',
        ];
    }

    public static function translationModel(): string
    {
        return ChallengeTranslation::class;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('innovation')
            ->logOnly(['registration_opens_at', 'registration_closes_at', 'submission_closes_at', 'results_on', 'results_published_at', 'max_team_size', 'criteria'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_image_id');
    }

    /**
     * @return HasMany<ChallengeTeam, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(ChallengeTeam::class);
    }

    public function title(?string $locale = null): string
    {
        return (string) $this->translationOrAny($locale)?->getAttribute('title');
    }

    public function phase(?CarbonImmutable $now = null): string
    {
        $now ??= CarbonImmutable::now();

        return match (true) {
            $this->results_published_at !== null => self::PHASE_RESULTS,
            $now < $this->registration_opens_at => self::PHASE_UPCOMING,
            $now < $this->registration_closes_at => self::PHASE_REGISTRATION,
            $now < $this->submission_closes_at => self::PHASE_BUILDING,
            default => self::PHASE_JUDGING,
        };
    }

    /** Teams may change their submission until submissions close. */
    public function takesSubmissions(?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now();

        return $now >= $this->registration_opens_at && $now < $this->submission_closes_at;
    }

    /**
     * The rubric with labels in a language; weights are whole numbers, normalised when scoring.
     *
     * @return list<array{key: string, label: string, weight: int}>
     */
    public function criteriaIn(?string $locale = null): array
    {
        $criteria = [];
        foreach ($this->criteria ?? [] as $criterion) {
            $label = LocalisedText::pick(is_array($criterion['labels'] ?? null) ? $criterion['labels'] : [], $locale);
            $key = (string) ($criterion['key'] ?? '');
            if ($label !== '' && preg_match('/^[a-z0-9_]{2,40}$/', $key) === 1) {
                $criteria[] = ['key' => $key, 'label' => $label, 'weight' => max(1, min(100, (int) ($criterion['weight'] ?? 1)))];
            }
        }

        return $criteria;
    }
}
