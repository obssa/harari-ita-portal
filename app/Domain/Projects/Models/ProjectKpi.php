<?php

namespace App\Domain\Projects\Models;

use App\Domain\Content\Concerns\TracksEditors;
use App\Domain\Status\Support\LocalisedText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A key performance indicator of a project (ADR-0024): what is measured, its unit, the baseline and the target, and
 * whether a higher or a lower number is better. Its dated readings show the progress; a KPI may name the dataset its
 * readings come from.
 *
 * @property int $id
 * @property string $project_id
 * @property array<string, string> $names
 * @property ?array<string, string> $units
 * @property ?string $baseline
 * @property string $target
 * @property bool $higher_is_better
 * @property ?string $dataset_id
 * @property int $sort
 * @property-read Project $project
 * @property-read ?ProjectKpiReading $latestReading
 */
class ProjectKpi extends Model
{
    use LogsActivity;
    use TracksEditors;

    protected $fillable = ['names', 'units', 'baseline', 'target', 'higher_is_better', 'dataset_id', 'sort'];

    protected $attributes = ['higher_is_better' => true, 'sort' => 0];

    protected function casts(): array
    {
        return [
            'names' => 'array',
            'units' => 'array',
            'baseline' => 'decimal:4',
            'target' => 'decimal:4',
            'higher_is_better' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('projects')->logOnly($this->fillable)->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<ProjectKpiReading, $this>
     */
    public function readings(): HasMany
    {
        return $this->hasMany(ProjectKpiReading::class)->orderBy('as_of');
    }

    /**
     * @return HasOne<ProjectKpiReading, $this>
     */
    public function latestReading(): HasOne
    {
        return $this->hasOne(ProjectKpiReading::class)->ofMany(['as_of' => 'max', 'id' => 'max']);
    }

    public function name(?string $locale = null): string
    {
        return LocalisedText::pick($this->names, $locale);
    }

    public function unit(?string $locale = null): string
    {
        return LocalisedText::pick($this->units, $locale);
    }

    /**
     * How far the latest reading has come from the baseline towards the target, 0 to 100; null without a reading.
     * Without a baseline the way is measured from zero. Works for "lower is better" targets too.
     */
    public function progress(?float $value = null): ?int
    {
        $value ??= $this->latestReading !== null ? (float) $this->latestReading->value : null;
        if ($value === null) {
            return null;
        }
        $start = $this->baseline !== null ? (float) $this->baseline : 0.0;
        $target = (float) $this->target;
        $way = $target - $start;
        if ($way == 0.0) {
            $met = $this->higher_is_better ? $value >= $target : $value <= $target;

            return $met ? 100 : 0;
        }

        return (int) max(0, min(100, round(($value - $start) / $way * 100)));
    }

    /** The latest reading meets the target. */
    public function met(): bool
    {
        if ($this->latestReading === null) {
            return false;
        }
        $value = (float) $this->latestReading->value;

        return $this->higher_is_better ? $value >= (float) $this->target : $value <= (float) $this->target;
    }
}
