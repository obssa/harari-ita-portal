<?php

namespace App\Domain\Projects\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One measured value of a KPI on a date (ADR-0024). One reading per KPI per date: a second one replaces it.
 *
 * @property int $id
 * @property int $project_kpi_id
 * @property string $value
 * @property CarbonImmutable $as_of
 * @property ?string $note
 * @property ?int $created_by
 * @property-read ProjectKpi $kpi
 */
class ProjectKpiReading extends Model
{
    use LogsActivity;

    protected $fillable = ['value', 'as_of', 'note'];

    protected function casts(): array
    {
        return ['value' => 'decimal:4', 'as_of' => 'immutable_date'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('projects')->logOnly(['value', 'as_of', 'note'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<ProjectKpi, $this>
     */
    public function kpi(): BelongsTo
    {
        return $this->belongsTo(ProjectKpi::class, 'project_kpi_id');
    }
}
