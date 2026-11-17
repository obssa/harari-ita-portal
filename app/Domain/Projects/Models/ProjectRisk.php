<?php

namespace App\Domain\Projects\Models;

use App\Domain\Content\Concerns\TracksEditors;
use App\Domain\Projects\Enums\RiskLevel;
use App\Domain\Projects\Enums\RiskStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A risk to a project (ADR-0024), for staff only: never on a public page, in the API or in the search index. Its
 * score is likelihood times impact (1 to 9).
 *
 * @property int $id
 * @property string $project_id
 * @property string $title
 * @property ?string $description
 * @property RiskLevel $likelihood
 * @property RiskLevel $impact
 * @property RiskStatus $status
 * @property ?int $owner_id
 * @property ?string $mitigation
 * @property ?CarbonImmutable $review_on
 * @property-read Project $project
 * @property-read ?User $owner
 */
class ProjectRisk extends Model
{
    use LogsActivity;
    use TracksEditors;

    protected $fillable = ['title', 'description', 'likelihood', 'impact', 'status', 'owner_id', 'mitigation', 'review_on'];

    protected $attributes = ['status' => 'open'];

    protected function casts(): array
    {
        return [
            'likelihood' => RiskLevel::class,
            'impact' => RiskLevel::class,
            'status' => RiskStatus::class,
            'review_on' => 'immutable_date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        // The texts may name people or suppliers: only the levels, status and dates are logged.
        return LogOptions::defaults()->useLogName('projects')->logOnly(['likelihood', 'impact', 'status', 'owner_id', 'review_on'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function score(): int
    {
        return $this->likelihood->weight() * $this->impact->weight();
    }
}
