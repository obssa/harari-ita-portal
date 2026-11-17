<?php

namespace App\Domain\Projects\Policies;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Projects\Models\Project;
use App\Models\User;

/**
 * Projects (PLAN.md §8): `projects.view` to see them in the admin,
 * `projects.manage` to create, publish and remove them. A Project Manager
 * scoped to a directorate sees and changes only its projects (ADR-0024).
 */
final class ProjectPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('projects.view') || $actor->can('projects.manage');
    }

    public function view(User $actor, Project $project): bool
    {
        return OfficeScope::covers($actor, $project->office_id, 'projects.view')
            || OfficeScope::covers($actor, $project->office_id, 'projects.manage');
    }

    public function create(User $actor): bool
    {
        return $actor->can('projects.manage');
    }

    public function update(User $actor, Project $project): bool
    {
        return OfficeScope::covers($actor, $project->office_id, 'projects.manage');
    }

    public function delete(User $actor, Project $project): bool
    {
        return OfficeScope::covers($actor, $project->office_id, 'projects.manage');
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }

    public function restore(User $actor, Project $project): bool
    {
        return OfficeScope::covers($actor, $project->office_id, 'projects.manage');
    }

    public function restoreAny(User $actor): bool
    {
        return false;
    }

    public function forceDelete(User $actor, Project $project): bool
    {
        return false;
    }

    public function forceDeleteAny(User $actor): bool
    {
        return false;
    }

    public function replicate(User $actor, Project $project): bool
    {
        return false;
    }

    public function reorder(User $actor): bool
    {
        return $actor->can('projects.manage');
    }
}
