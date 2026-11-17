<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Projects\Models\ProjectKpi;
use App\Domain\Projects\Models\ProjectKpiReading;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Records a KPI's value on a date (ADR-0024). A second reading for the same date replaces the first; a date in the
 * future is refused. `remove()` deletes one reading.
 */
final class RecordKpiReading
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(ProjectKpi $kpi, array $data, User $actor): ProjectKpiReading
    {
        if (! $actor->can('update', $kpi->project)) {
            throw new AuthorizationException;
        }
        if (! is_numeric($data['value'] ?? null)) {
            throw ValidationException::withMessages(['value' => __('projects.validation.kpi_number')]);
        }

        try {
            $asOf = CarbonImmutable::parse((string) ($data['as_of'] ?? ''))->startOfDay();
        } catch (Throwable) {
            throw ValidationException::withMessages(['as_of' => __('organisation.validation.date')]);
        }
        if ($asOf->greaterThan(CarbonImmutable::today())) {
            throw ValidationException::withMessages(['as_of' => __('projects.validation.reading_future')]);
        }

        /** @var ProjectKpiReading $reading */
        $reading = $kpi->readings()->firstOrNew(['as_of' => $asOf->toDateString()]);
        $reading->fill([
            'value' => (string) $data['value'],
            'note' => filled($data['note'] ?? null) ? mb_substr(trim((string) $data['note']), 0, 300) : null,
        ]);
        $reading->created_by ??= $actor->id;
        $reading->save();
        $kpi->project->touch();

        return $reading;
    }

    public function remove(ProjectKpiReading $reading, User $actor): void
    {
        if (! $actor->can('update', $reading->kpi->project)) {
            throw new AuthorizationException;
        }
        $reading->delete();
        $reading->kpi->project->touch();
    }
}
