<?php

namespace App\Domain\Academy\Policies;

use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Support\CohortScope;
use App\Models\User;

/**
 * Cohorts (ADR-0028): `cohorts.manage` to plan, change and cancel them; `cohorts.view` to see them, which for an
 * instructor means only the cohorts they teach; `cohorts.attendance` to mark attendance in those cohorts.
 */
final class CourseCohortPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('cohorts.view') || $actor->can('cohorts.manage');
    }

    public function view(User $actor, CourseCohort $cohort): bool
    {
        return $this->viewAny($actor) && CohortScope::covers($actor, $cohort);
    }

    public function create(User $actor): bool
    {
        return $actor->can('cohorts.manage');
    }

    public function update(User $actor, CourseCohort $cohort): bool
    {
        return $actor->can('cohorts.manage');
    }

    /** A cohort with enrolments is cancelled, not deleted. */
    public function delete(User $actor, CourseCohort $cohort): bool
    {
        return $actor->can('cohorts.manage') && $cohort->enrolments()->doesntExist();
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }

    public function attendance(User $actor, CourseCohort $cohort): bool
    {
        return $actor->can('cohorts.attendance') && CohortScope::covers($actor, $cohort);
    }

    public function issueCertificates(User $actor, CourseCohort $cohort): bool
    {
        return $actor->can('certificates.issue') && CohortScope::covers($actor, $cohort);
    }
}
