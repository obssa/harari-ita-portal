<?php

namespace App\Domain\Innovation\Policies;

use App\Domain\Innovation\Models\ChallengeTeam;
use App\Models\User;

/**
 * Challenge teams (PLAN.md §7; ADR-0022): staff with `challenges.view` read them, `challenges.manage` sets the award
 * line. Teams register themselves; the retention purge or a privacy erasure removes them.
 */
final class ChallengeTeamPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('challenges.view') || $actor->can('challenges.manage');
    }

    public function view(User $actor, ChallengeTeam $team): bool
    {
        return $this->viewAny($actor);
    }

    public function award(User $actor, ChallengeTeam $team): bool
    {
        return $actor->can('challenges.manage');
    }

    /** ADR-0053: the people who run the challenge write to its teams. */
    public function message(User $actor, ChallengeTeam $team): bool
    {
        return $actor->can('challenges.manage') && $team->erased_at === null;
    }

    public function create(User $actor): bool
    {
        return false;
    }

    public function update(User $actor, ChallengeTeam $team): bool
    {
        return false;
    }

    public function delete(User $actor, ChallengeTeam $team): bool
    {
        return false;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }

    public function forceDelete(User $actor, ChallengeTeam $team): bool
    {
        return false;
    }

    public function forceDeleteAny(User $actor): bool
    {
        return false;
    }

    public function restore(User $actor, ChallengeTeam $team): bool
    {
        return false;
    }

    public function restoreAny(User $actor): bool
    {
        return false;
    }

    public function replicate(User $actor, ChallengeTeam $team): bool
    {
        return false;
    }

    public function reorder(User $actor): bool
    {
        return false;
    }
}
