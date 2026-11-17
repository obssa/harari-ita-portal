<?php

namespace App\Domain\Academy\Actions;

use App\Domain\Academy\Models\Certificate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Revokes a certificate with a reason kept for staff (ADR-0030). Verification then answers "revoked", without the
 * reason. A revoked certificate is never restored; a new one can be issued instead.
 */
final class RevokeCertificate
{
    public function handle(Certificate $certificate, string $reason, User $actor): Certificate
    {
        if (! $actor->can('revoke', $certificate)) {
            throw new AuthorizationException;
        }
        $reason = trim($reason);
        if (mb_strlen($reason) < 3) {
            throw ValidationException::withMessages(['data.reason' => __('academy.validation.revoke_reason')]);
        }

        $certificate->revoked_at = CarbonImmutable::now();
        $certificate->revoked_by = $actor->id;
        $certificate->revoke_reason = mb_substr($reason, 0, 300);
        $certificate->save();

        return $certificate;
    }
}
