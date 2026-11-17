<?php

namespace App\Domain\Projects\Policies;

use App\Domain\Projects\Models\ProjectKpi;
use App\Models\User;

/**
 * A project's KPIs (ADR-0024) follow the project: whoever may change the project changes its KPIs and readings.
 */
final class ProjectKpiPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('projects.view') || $actor->can('projects.manage');
    }

    public function view(User $actor, ProjectKpi $kpi): bool
    {
        return $actor->can('view', $kpi->project);
    }

    public function create(User $actor): bool
    {
        return $actor->can('projects.manage');
    }

    public function update(User $actor, ProjectKpi $kpi): bool
    {
        return $actor->can('update', $kpi->project);
    }

    public function delete(User $actor, ProjectKpi $kpi): bool
    {
        return $actor->can('update', $kpi->project);
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
