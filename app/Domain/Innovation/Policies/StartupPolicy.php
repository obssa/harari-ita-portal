<?php

namespace App\Domain\Innovation\Policies;

use App\Domain\Organisation\Policies\DirectoryPolicy;

/**
 * The startups directory (PLAN.md §7; ADR-0020): `startups.view` to see listings in the admin, `startups.manage` to
 * edit, review and verify them.
 */
final class StartupPolicy extends DirectoryPolicy
{
    protected function module(): string
    {
        return 'startups';
    }
}
