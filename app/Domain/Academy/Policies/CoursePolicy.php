<?php

namespace App\Domain\Academy\Policies;

use App\Domain\Organisation\Policies\DirectoryPolicy;

/**
 * Courses (PLAN.md §7, ADR-0028): `courses.view` to see them in the admin, `courses.manage` to change, publish and
 * remove them.
 */
final class CoursePolicy extends DirectoryPolicy
{
    protected function module(): string
    {
        return 'courses';
    }
}
