<?php

namespace App\Domain\Innovation\Policies;

use App\Domain\Innovation\Models\Application;
use App\Models\User;

/**
 * Applications (PLAN.md §7; ADR-0021): `applications.view` to read, `applications.review` to move and score,
 * `applications.decide` to accept, waitlist or decline. Applications are never created or deleted in the admin:
 * applicants send them, and the retention purge or a privacy erasure removes them.
 */
final class ApplicationPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('applications.view');
    }

    public function view(User $actor, Application $application): bool
    {
        return $actor->can('applications.view');
    }

    public function review(User $actor, Application $application): bool
    {
        return $actor->can('applications.review');
    }

    public function decide(User $actor, Application $application): bool
    {
        return $actor->can('applications.decide');
    }

    public function create(User $actor): bool
    {
        return false;
    }

    public function update(User $actor, Application $application): bool
    {
        return false;
    }

    public function delete(User $actor, Application $application): bool
    {
        return false;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }

    public function forceDelete(User $actor, Application $application): bool
    {
        return false;
    }

    public function forceDeleteAny(User $actor): bool
    {
        return false;
    }

    public function restore(User $actor, Application $application): bool
    {
        return false;
    }

    public function restoreAny(User $actor): bool
    {
        return false;
    }

    public function replicate(User $actor, Application $application): bool
    {
        return false;
    }

    public function reorder(User $actor): bool
    {
        return false;
    }
}
