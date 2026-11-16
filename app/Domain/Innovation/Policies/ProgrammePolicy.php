<?php

namespace App\Domain\Innovation\Policies;

use App\Domain\Organisation\Policies\DirectoryPolicy;

/**
 * Programmes and their cohorts (PLAN.md §7; ADR-0021): `programmes.view` to see them, `programmes.manage` to change
 * and publish them.
 */
final class ProgrammePolicy extends DirectoryPolicy
{
    protected function module(): string
    {
        return 'programmes';
    }
}
