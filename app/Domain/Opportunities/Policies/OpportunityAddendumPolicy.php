<?php

namespace App\Domain\Opportunities\Policies;

use App\Domain\Opportunities\Models\OpportunityAddendum;
use App\Models\User;

/**
 * The log of an opportunity (ADR-0033) follows the opportunity: those who manage opportunities add and correct
 * entries; nobody deletes one, so the record of what bidders were told stays whole.
 */
final class OpportunityAddendumPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('opportunities.view') || $actor->can('opportunities.manage');
    }

    public function view(User $actor, OpportunityAddendum $addendum): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('opportunities.manage');
    }

    public function update(User $actor, OpportunityAddendum $addendum): bool
    {
        return $actor->can('opportunities.manage');
    }

    public function delete(User $actor, OpportunityAddendum $addendum): bool
    {
        return false;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
