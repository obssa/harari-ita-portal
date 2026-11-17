<?php

namespace App\Domain\Academy\Policies;

use App\Domain\Academy\Models\Certificate;
use App\Domain\Academy\Support\CohortScope;
use App\Models\User;

/**
 * Certificates (ADR-0030): `certificates.issue` to issue and print them, `certificates.revoke` to revoke one. They are
 * never edited or deleted: a mistake is revoked and issued again.
 */
final class CertificatePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('certificates.issue') || $actor->can('certificates.revoke');
    }

    public function view(User $actor, Certificate $certificate): bool
    {
        return $this->viewAny($actor) && CohortScope::covers($actor, $certificate->loadMissing('cohort')->cohort);
    }

    public function create(User $actor): bool
    {
        return $actor->can('certificates.issue');
    }

    public function update(User $actor, Certificate $certificate): bool
    {
        return false;
    }

    public function revoke(User $actor, Certificate $certificate): bool
    {
        return $actor->can('certificates.revoke') && ! $certificate->isRevoked();
    }

    public function delete(User $actor, Certificate $certificate): bool
    {
        return false;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
