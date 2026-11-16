<?php

namespace App\Domain\Innovation\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use App\Domain\Content\Concerns\HasTranslations;
use App\Domain\Content\Concerns\TracksEditors;
use App\Domain\Content\Contracts\TranslatableModel;
use App\Domain\Innovation\Enums\ProgrammeKind;
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
 * A programme the Agency runs or runs with a partner (ADR-0021): incubation, acceleration or training. Its words are
 * per language; the questions applicants answer are shared, with their labels per language.
 *
 * @property string $id
 * @property ProgrammeKind $kind
 * @property ?string $partner
 * @property ?int $cover_image_id
 * @property ?list<array<string, mixed>> $questions read through questionsIn()
 * @property int $sort
 * @property-read ?Media $coverImage
 * @property-read Collection<int, Cohort> $cohorts
 */
class Programme extends Model implements TranslatableModel
{
    use BelongsToSite;

    /** @use HasTranslations<ProgrammeTranslation> */
    use HasTranslations;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;
    use TracksEditors;

    public const MAX_QUESTIONS = 10;

    protected $fillable = ['kind', 'partner', 'cover_image_id', 'questions', 'sort'];

    protected $attributes = ['sort' => 0];

    protected function casts(): array
    {
        return ['kind' => ProgrammeKind::class, 'questions' => 'array', 'sort' => 'integer'];
    }

    public static function translationModel(): string
    {
        return ProgrammeTranslation::class;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('innovation')
            ->logOnly(['kind', 'partner', 'cover_image_id', 'questions', 'sort'])
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
     * @return HasMany<Cohort, $this>
     */
    public function cohorts(): HasMany
    {
        return $this->hasMany(Cohort::class)->orderBy('opens_at');
    }

    public function title(?string $locale = null): string
    {
        return (string) $this->translationOrAny($locale)?->getAttribute('title');
    }

    /** The cohort taking applications now, if any. */
    public function openCohort(?CarbonImmutable $now = null): ?Cohort
    {
        $now ??= CarbonImmutable::now();

        return $this->cohorts->first(fn (Cohort $cohort): bool => $cohort->isOpen($now));
    }

    /** The next cohort that has not opened yet, if any. */
    public function nextCohort(?CarbonImmutable $now = null): ?Cohort
    {
        $now ??= CarbonImmutable::now();

        return $this->cohorts->first(fn (Cohort $cohort): bool => $cohort->opens_at > $now);
    }

    /**
     * The questions with their label in a language.
     *
     * @return list<array{key: string, label: string, long: bool, required: bool}>
     */
    public function questionsIn(?string $locale = null): array
    {
        $questions = [];
        foreach ($this->questions ?? [] as $question) {
            $label = LocalisedText::pick($question['labels'] ?? [], $locale);
            if ($label !== '' && preg_match('/^[a-z0-9_]{2,40}$/', (string) ($question['key'] ?? '')) === 1) {
                $questions[] = ['key' => (string) $question['key'], 'label' => $label, 'long' => (bool) ($question['long'] ?? false), 'required' => (bool) ($question['required'] ?? false)];
            }
        }

        return $questions;
    }
}
