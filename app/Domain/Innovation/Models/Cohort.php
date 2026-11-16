<?php

namespace App\Domain\Innovation\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One round of a programme (ADR-0021): when applications open and close, when it runs and how many places it has.
 *
 * @property int $id
 * @property string $programme_id
 * @property string $name
 * @property CarbonImmutable $opens_at
 * @property CarbonImmutable $closes_at
 * @property ?CarbonImmutable $starts_on
 * @property ?CarbonImmutable $ends_on
 * @property ?int $places
 * @property-read Programme $programme
 */
class Cohort extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'opens_at', 'closes_at', 'starts_on', 'ends_on', 'places'];

    protected function casts(): array
    {
        return [
            'opens_at' => 'immutable_datetime',
            'closes_at' => 'immutable_datetime',
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'places' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('innovation')->logOnly(['name', 'opens_at', 'closes_at', 'starts_on', 'ends_on', 'places'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Programme, $this>
     */
    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function isOpen(?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now();

        return $this->opens_at <= $now && $now < $this->closes_at;
    }
}
