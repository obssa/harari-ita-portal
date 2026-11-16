<?php

namespace App\Domain\Status\Policies;

use App\Domain\Organisation\Policies\DirectoryPolicy;

/**
 * Systems on the status page (PLAN.md §7; ADR-0018): `status.view` to see them in the admin, `status.manage` to
 * change them.
 */
final class StatusComponentPolicy extends DirectoryPolicy
{
    protected function module(): string
    {
        return 'status';
    }
}
