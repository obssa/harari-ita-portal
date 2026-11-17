<?php

namespace App\Domain\Academy\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use App\Domain\Status\Support\LocalisedText;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Someone who teaches cohorts (ADR-0028): a name, a title per language and, for staff, their account, through which
 * they mark attendance for the cohorts they teach. Shown on public pages only once their consent is recorded.
 *
 * @property int $id
 * @property ?int $user_id
 * @property string $name
 * @property ?array<string, string> $titles
 * @property bool $public
 * @property ?CarbonImmutable $consented_at
 * @property-read ?User $user
 * @property-read Collection<int, CourseCohort> $cohorts
 */
class Instructor extends Model
{
    use BelongsToSite;
    use LogsActivity;

    protected $fillable = ['user_id', 'name', 'titles', 'public', 'consented_at'];

    protected $attributes = ['public' => false];

    protected function casts(): array
    {
        return ['titles' => 'array', 'public' => 'boolean', 'consented_at' => 'immutable_datetime'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('academy')->logOnly(['user_id', 'public', 'consented_at'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<CourseCohort, $this>
     */
    public function cohorts(): BelongsToMany
    {
        return $this->belongsToMany(CourseCohort::class, 'course_cohort_instructor');
    }

    public function title(?string $locale = null): string
    {
        return LocalisedText::pick($this->titles, $locale);
    }

    public function isShown(): bool
    {
        return $this->public && $this->consented_at !== null;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeShown(Builder $query): Builder
    {
        return $query->where('public', true)->whereNotNull('consented_at');
    }
}
