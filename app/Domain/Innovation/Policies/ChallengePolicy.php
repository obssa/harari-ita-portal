<?php

namespace App\Domain\Innovation\Policies;

use App\Domain\Organisation\Policies\DirectoryPolicy;

/**
 * Challenges (PLAN.md §7; ADR-0022): `challenges.view` to see them, `challenges.manage` to change and publish them,
 * assign judges and publish the results.
 */
final class ChallengePolicy extends DirectoryPolicy
{
    protected function module(): string
    {
        return 'challenges';
    }
}
