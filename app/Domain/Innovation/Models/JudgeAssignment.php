<?php

namespace App\Domain\Innovation\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A judge assigned to a team (ADR-0022): the judge's rubric scores and weighted total, or a declared conflict of
 * interest, which takes the assignment out of the results.
 *
 * @property int $id
 * @property string $challenge_team_id
 * @property int $user_id
 * @property ?array<string, int> $scores
 * @property ?string $total
 * @property ?string $comment
 * @property ?CarbonImmutable $scored_at
 * @property ?CarbonImmutable $conflict_declared_at
 * @property ?string $conflict_note
 * @property-read ChallengeTeam $team
 * @property-read User $judge
 */
class JudgeAssignment extends Model
{
    use LogsActivity;

    protected $fillable = ['challenge_team_id', 'user_id'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'scores' => 'array', 'scored_at' => 'immutable_datetime', 'conflict_declared_at' => 'immutable_datetime'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('innovation')
            ->logOnly(['user_id', 'total', 'scored_at', 'conflict_declared_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<ChallengeTeam, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(ChallengeTeam::class, 'challenge_team_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('conflict_declared_at');
    }
}
