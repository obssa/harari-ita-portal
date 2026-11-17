<?php

namespace App\Domain\Academy\Policies;

use App\Domain\Academy\Models\Instructor;
use App\Models\User;

/**
 * Instructors (ADR-0028) are kept by those who manage courses; anyone who sees courses sees the list.
 */
final class InstructorPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('courses.view') || $actor->can('courses.manage');
    }

    public function view(User $actor, Instructor $instructor): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('courses.manage');
    }

    public function update(User $actor, Instructor $instructor): bool
    {
        return $actor->can('courses.manage');
    }

    public function delete(User $actor, Instructor $instructor): bool
    {
        return $actor->can('courses.manage');
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
