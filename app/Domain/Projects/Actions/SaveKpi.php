<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectKpi;
use App\Domain\Status\Support\LocalisedText;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Adds or changes a project's KPI (ADR-0024): a name in at least one language, an optional unit, the baseline, the
 * target, and whether higher or lower is better.
 */
final class SaveKpi
{
    public const MAX_PER_PROJECT = 12;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Project $project, ProjectKpi $kpi, array $data, User $actor): ProjectKpi
    {
        if (! $actor->can('update', $project)) {
            throw new AuthorizationException;
        }

        $names = LocalisedText::clean($data['names'] ?? [], 160);
        $errors = [];
        if ($names === []) {
            $errors['names'] = __('projects.validation.kpi_name');
        }
        if (! is_numeric($data['target'] ?? null)) {
            $errors['target'] = __('projects.validation.kpi_target');
        }
        $baseline = $data['baseline'] ?? null;
        if (filled($baseline) && ! is_numeric($baseline)) {
            $errors['baseline'] = __('projects.validation.kpi_number');
        }
        if (! $kpi->exists && $project->kpis()->count() >= self::MAX_PER_PROJECT) {
            $errors['names'] = __('projects.validation.kpi_max', ['max' => self::MAX_PER_PROJECT]);
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $kpi->fill([
            'names' => $names,
            'units' => LocalisedText::clean($data['units'] ?? [], 40) ?: null,
            'baseline' => filled($baseline) ? (string) $baseline : null,
            'target' => (string) $data['target'],
            'higher_is_better' => (bool) ($data['higher_is_better'] ?? true),
            'sort' => (int) ($data['sort'] ?? ($kpi->exists ? $kpi->sort : $project->kpis()->count())),
        ]);
        $kpi->project()->associate($project);
        $kpi->save();
        $project->touch();

        return $kpi;
    }
}
