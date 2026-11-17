<?php

namespace App\Domain\Academy\Actions;

use App\Domain\Academy\Models\Enrolment;

/**
 * What enrolling did: a new place (or a place on the waiting list), or the learner's existing enrolment again.
 */
final class EnrolmentResult
{
    public function __construct(
        public readonly Enrolment $enrolment,
        public readonly bool $existing,
    ) {}
}
