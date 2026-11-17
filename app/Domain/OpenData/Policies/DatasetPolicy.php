<?php

namespace App\Domain\OpenData\Policies;

use App\Domain\Organisation\Policies\DirectoryPolicy;

/**
 * Datasets (PLAN.md §7, ADR-0025): `datasets.view` to see them in the admin, `datasets.manage` to change them and their
 * files, `datasets.publish` to publish a language (checked when the languages are written).
 */
final class DatasetPolicy extends DirectoryPolicy
{
    protected function module(): string
    {
        return 'datasets';
    }
}
