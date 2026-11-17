<?php

namespace App\Domain\Projects\Models;

use App\Domain\Status\Support\LocalisedText;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A step of a project (ADR-0024): a short title per language, when it is due and when it was done.
 *
 * @property int $id
 * @property string $project_id
 * @property array<string, string> $titles
 * @property ?CarbonImmutable $due_on
 * @property ?CarbonImmutable $done_on
 * @property int $sort
 * @property-read Project $project
 */
class ProjectMilestone extends Model
{
    use LogsActivity;

    protected $fillable = ['titles', 'due_on', 'done_on', 'sort'];

    protected $attributes = ['sort' => 0];

    protected function casts(): array
    {
        return ['titles' => 'array', 'due_on' => 'immutable_date', 'done_on' => 'immutable_date', 'sort' => 'integer'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('projects')->logOnly(['titles', 'due_on', 'done_on', 'sort'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function title(?string $locale = null): string
    {
        return LocalisedText::pick($this->titles, $locale);
    }

    public function isDone(): bool
    {
        return $this->done_on !== null;
    }

    /** Due before today and not done. */
    public function isOverdue(?CarbonImmutable $today = null): bool
    {
        $today ??= CarbonImmutable::today();

        return $this->done_on === null && $this->due_on !== null && $this->due_on->lessThan($today);
    }
}
