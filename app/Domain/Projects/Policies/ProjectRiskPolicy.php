<?php

namespace App\Domain\Projects\Policies;

use App\Domain\Projects\Models\ProjectRisk;
use App\Models\User;

/**
 * Project risks (ADR-0024) are for staff only and follow the project: whoever may see the project sees its risks,
 * whoever may change it changes them.
 */
final class ProjectRiskPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('projects.view') || $actor->can('projects.manage');
    }

    public function view(User $actor, ProjectRisk $risk): bool
    {
        return $actor->can('view', $risk->project);
    }

    public function create(User $actor): bool
    {
        return $actor->can('projects.manage');
    }

    public function update(User $actor, ProjectRisk $risk): bool
    {
        return $actor->can('update', $risk->project);
    }

    public function delete(User $actor, ProjectRisk $risk): bool
    {
        return $actor->can('update', $risk->project);
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
