<?php

namespace App\Domain\Academy\Policies;

use App\Domain\Academy\Models\Enrolment;
use App\Domain\Academy\Support\CohortScope;
use App\Models\User;

/**
 * Enrolments (ADR-0029) follow their cohort: those who see the cohort see its learners; those who manage cohorts
 * change their status. Learners enrol and give their place back on the public site, not here.
 */
final class EnrolmentPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('cohorts.view') || $actor->can('cohorts.manage');
    }

    public function view(User $actor, Enrolment $enrolment): bool
    {
        return $this->viewAny($actor) && CohortScope::covers($actor, $enrolment->loadMissing('cohort')->cohort);
    }

    public function create(User $actor): bool
    {
        return false;
    }

    public function update(User $actor, Enrolment $enrolment): bool
    {
        return $actor->can('cohorts.manage');
    }

    public function delete(User $actor, Enrolment $enrolment): bool
    {
        return false;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
