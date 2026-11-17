<?php

namespace App\Domain\Opportunities\Policies;

use App\Domain\Organisation\Policies\DirectoryPolicy;

/**
 * Opportunities (PLAN.md §7, ADR-0032): `opportunities.view` to see them in the admin, `opportunities.manage` to
 * change, publish and remove them (the Procurement Officer).
 */
final class OpportunityPolicy extends DirectoryPolicy
{
    protected function module(): string
    {
        return 'opportunities';
    }
}
