<?php

namespace App\Domain\Organisation\Policies;

final class PillarPolicy extends DirectoryPolicy
{
    protected function module(): string
    {
        return 'pillars';
    }
}
