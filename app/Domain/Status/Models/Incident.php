<?php

namespace App\Domain\Status\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use App\Domain\Content\Concerns\TracksEditors;
use App\Domain\Status\Enums\ComponentState;
use App\Domain\Status\Enums\IncidentKind;
use App\Domain\Status\Enums\IncidentStage;
use App\Domain\Status\Support\LocalisedText;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * An incident or planned maintenance on one or more systems (ADR-0018). It is active from `started_at` until it is
 * resolved; while active, its systems show its impact.
 *
 * @property string $id
 * @property IncidentKind $kind
 * @property array<string, string> $titles
 * @property ComponentState $impact
 * @property IncidentStage $stage
 * @property CarbonImmutable $started_at
 * @property ?CarbonImmutable $resolved_at
 * @property CarbonImmutable $created_at
 * @property-read Collection<int, StatusComponent> $components
 * @property-read Collection<int, IncidentUpdate> $updates
 */
class Incident extends Model
{
    use BelongsToSite;
    use HasUlids;
    use LogsActivity;
    use TracksEditors;

    protected $fillable = ['kind', 'titles', 'impact', 'stage', 'started_at', 'resolved_at'];

    protected function casts(): array
    {
        return [
            'kind' => IncidentKind::class,
            'titles' => 'array',
            'impact' => ComponentState::class,
            'stage' => IncidentStage::class,
            'started_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('status')->logOnly(['kind', 'titles', 'impact', 'stage', 'started_at', 'resolved_at'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function title(?string $locale = null): string
    {
        return LocalisedText::pick($this->titles, $locale);
    }

    /**
     * @return BelongsToMany<StatusComponent, $this>
     */
    public function components(): BelongsToMany
    {
        return $this->belongsToMany(StatusComponent::class);
    }

    /**
     * @return HasMany<IncidentUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function isActive(?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now();

        return $this->resolved_at === null && $this->started_at <= $now;
    }

    public function isUpcoming(?CarbonImmutable $now = null): bool
    {
        return $this->resolved_at === null && $this->started_at > ($now ?? CarbonImmutable::now());
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('resolved_at')->where('started_at', '<=', now());
    }
}
