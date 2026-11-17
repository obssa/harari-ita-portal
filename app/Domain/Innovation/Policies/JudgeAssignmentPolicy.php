<?php

namespace App\Domain\Innovation\Policies;

use App\Domain\Innovation\Models\JudgeAssignment;
use App\Models\User;

/**
 * A judge's own assignments (PLAN.md §7 "Judge (limited)"; ADR-0022): a judge holding `judging.score` sees and
 * scores only the teams assigned to them. Staff assign judges from the challenge, never here.
 */
final class JudgeAssignmentPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('judging.score');
    }

    public function view(User $actor, JudgeAssignment $assignment): bool
    {
        return $actor->can('judging.score') && $assignment->user_id === $actor->id;
    }

    public function score(User $actor, JudgeAssignment $assignment): bool
    {
        return $this->view($actor, $assignment);
    }

    public function create(User $actor): bool
    {
        return false;
    }

    public function update(User $actor, JudgeAssignment $assignment): bool
    {
        return false;
    }

    public function delete(User $actor, JudgeAssignment $assignment): bool
    {
        return false;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }

    public function forceDelete(User $actor, JudgeAssignment $assignment): bool
    {
        return false;
    }

    public function forceDeleteAny(User $actor): bool
    {
        return false;
    }

    public function restore(User $actor, JudgeAssignment $assignment): bool
    {
        return false;
    }

    public function restoreAny(User $actor): bool
    {
        return false;
    }

    public function replicate(User $actor, JudgeAssignment $assignment): bool
    {
        return false;
    }

    public function reorder(User $actor): bool
    {
        return false;
    }
}
