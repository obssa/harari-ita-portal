<?php

namespace App\Domain\Projects\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use App\Domain\Content\Concerns\HasTranslations;
use App\Domain\Content\Concerns\TracksEditors;
use App\Domain\Content\Contracts\TranslatableModel;
use App\Domain\Content\Models\Category;
use App\Domain\Media\Models\Gallery;
use App\Domain\Media\Models\Media;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Enums\RagStatus;
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
 * A development project (PLAN.md §4 Programmes & Achievements, ADR-0027):
 * sector, directorate or office, status and progress, dates, the budget when it
 * may be published, a map pin, photos and its own statistics.
 *
 * @property string $id
 * @property ?string $sector_id
 * @property ?string $office_id
 * @property ?string $pillar_id
 * @property ProjectStatus $status
 * @property ?RagStatus $rag_status
 * @property ?string $rag_reason
 * @property ?CarbonImmutable $rag_updated_at
 * @property ?CarbonImmutable $starts_on
 * @property ?CarbonImmutable $ends_on
 * @property ?string $budget
 * @property ?string $spent
 * @property bool $budget_public
 * @property ?int $progress
 * @property ?float $latitude
 * @property ?float $longitude
 * @property ?int $cover_id
 * @property ?string $gallery_id
 * @property bool $featured
 * @property-read ?Category $sector
 * @property-read ?Office $office
 * @property-read ?Pillar $pillar
 * @property-read Collection<int, ProjectMilestone> $milestones
 * @property-read Collection<int, ProjectKpi> $kpis
 * @property-read Collection<int, ProjectRisk> $risks
 * @property-read ?Media $cover
 * @property-read ?Gallery $gallery
 */
class Project extends Model implements TranslatableModel
{
    use BelongsToSite;

    /** @use HasTranslations<ProjectTranslation> */
    use HasTranslations;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;
    use TracksEditors;

    protected $fillable = [
        'sector_id', 'office_id', 'pillar_id', 'status', 'rag_status', 'rag_reason', 'rag_updated_at', 'starts_on', 'ends_on',
        'budget', 'spent', 'budget_public', 'progress',
        'latitude', 'longitude', 'cover_id', 'gallery_id', 'featured',
    ];

    protected $attributes = ['status' => 'planned', 'budget_public' => false, 'featured' => false];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'rag_status' => RagStatus::class,
            'rag_updated_at' => 'immutable_datetime',
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'budget' => 'decimal:2',
            'spent' => 'decimal:2',
            'budget_public' => 'boolean',
            'progress' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'featured' => 'boolean',
        ];
    }

    public static function translationModel(): string
    {
        return ProjectTranslation::class;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('projects')
            ->logOnly(array_values(array_diff($this->fillable, ['rag_reason'])))
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sector_id');
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * @return BelongsTo<Pillar, $this>
     */
    public function pillar(): BelongsTo
    {
        return $this->belongsTo(Pillar::class);
    }

    /**
     * @return HasMany<ProjectMilestone, $this>
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * @return HasMany<ProjectKpi, $this>
     */
    public function kpis(): HasMany
    {
        return $this->hasMany(ProjectKpi::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * Staff only (ADR-0024): never loaded by a public page, the API or the search index.
     *
     * @return HasMany<ProjectRisk, $this>
     */
    public function risks(): HasMany
    {
        return $this->hasMany(ProjectRisk::class)->orderBy('id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_id');
    }

    /**
     * @return BelongsTo<Gallery, $this>
     */
    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    /**
     * @return HasMany<Statistic, $this>
     */
    public function statistics(): HasMany
    {
        return $this->hasMany(Statistic::class)->orderBy('sort')->orderBy('id');
    }

    public function hasMap(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /** The spend for the public: only with a budget the public may see. */
    public function publicSpent(): ?float
    {
        return $this->publicBudget() !== null && $this->spent !== null ? (float) $this->spent : null;
    }

    /** The RAG status for the public: only while the project runs. */
    public function publicRag(): ?RagStatus
    {
        return $this->status === ProjectStatus::Ongoing ? $this->rag_status : null;
    }

    /** The budget for the public, only when an editor allowed it. */
    public function publicBudget(): ?float
    {
        return $this->budget_public && $this->budget !== null ? (float) $this->budget : null;
    }

    /** The name for the admin: this language, its fallbacks, or any language. */
    public function title(?string $locale = null): string
    {
        return (string) $this->translationOrAny($locale)?->getAttribute('title');
    }
}
