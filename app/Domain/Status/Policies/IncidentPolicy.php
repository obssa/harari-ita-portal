<?php

namespace App\Domain\Status\Policies;

use App\Domain\Organisation\Policies\DirectoryPolicy;

/**
 * Incidents and maintenance (PLAN.md §7; ADR-0018): `status.view` to see them in the admin, `status.manage` to
 * open, update and resolve them.
 */
final class IncidentPolicy extends DirectoryPolicy
{
    protected function module(): string
    {
        return 'status';
    }
}
