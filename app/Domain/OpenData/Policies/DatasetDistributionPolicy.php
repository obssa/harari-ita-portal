<?php

namespace App\Domain\OpenData\Policies;

use App\Domain\OpenData\Models\DatasetDistribution;
use App\Models\User;

/**
 * A dataset's files and links (ADR-0025) follow the dataset: whoever may change the dataset changes its distributions.
 */
final class DatasetDistributionPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('datasets.view') || $actor->can('datasets.manage');
    }

    public function view(User $actor, DatasetDistribution $distribution): bool
    {
        return $actor->can('view', $distribution->dataset);
    }

    public function create(User $actor): bool
    {
        return $actor->can('datasets.manage');
    }

    public function update(User $actor, DatasetDistribution $distribution): bool
    {
        return $actor->can('update', $distribution->dataset);
    }

    public function delete(User $actor, DatasetDistribution $distribution): bool
    {
        return $actor->can('update', $distribution->dataset);
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }

    /** Reordering the files of a dataset. */
    public function reorder(User $actor): bool
    {
        return $actor->can('datasets.manage');
    }
}
