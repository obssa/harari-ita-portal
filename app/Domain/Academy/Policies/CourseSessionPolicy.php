<?php

namespace App\Domain\Academy\Policies;

use App\Domain\Academy\Models\CourseSession;
use App\Domain\Academy\Support\CohortScope;
use App\Models\User;

/**
 * Sessions (ADR-0028) follow their cohort: those who see the cohort see its sessions. They are planned in the
 * cohort's form, so they are not created, changed or deleted on their own.
 */
final class CourseSessionPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('cohorts.view') || $actor->can('cohorts.manage');
    }

    public function view(User $actor, CourseSession $session): bool
    {
        return $this->viewAny($actor) && CohortScope::covers($actor, $session->loadMissing('cohort')->cohort);
    }

    public function create(User $actor): bool
    {
        return false;
    }

    public function update(User $actor, CourseSession $session): bool
    {
        return false;
    }

    public function delete(User $actor, CourseSession $session): bool
    {
        return false;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
