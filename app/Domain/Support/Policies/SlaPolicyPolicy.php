<?php

namespace App\Domain\Support\Policies;

use App\Domain\Support\Models\SlaPolicy;
use App\Models\User;

/**
 * SLA targets (ADR-0017): seen by everyone who handles tickets, changed with `tickets.manage`.
 */
final class SlaPolicyPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('tickets.view');
    }

    public function view(User $actor, SlaPolicy $policy): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('tickets.manage');
    }

    public function update(User $actor, SlaPolicy $policy): bool
    {
        return $actor->can('tickets.manage');
    }

    public function delete(User $actor, SlaPolicy $policy): bool
    {
        return $actor->can('tickets.manage');
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }

    public function forceDelete(User $actor, SlaPolicy $policy): bool
    {
        return false;
    }

    public function forceDeleteAny(User $actor): bool
    {
        return false;
    }

    public function restore(User $actor, SlaPolicy $policy): bool
    {
        return false;
    }

    public function restoreAny(User $actor): bool
    {
        return false;
    }

    public function replicate(User $actor, SlaPolicy $policy): bool
    {
        return false;
    }

    public function reorder(User $actor): bool
    {
        return false;
    }
}
