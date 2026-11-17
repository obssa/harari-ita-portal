<?php

namespace App\Domain\Academy\Actions;

use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\Enrolment;
use App\Domain\Academy\Support\AttendanceRule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Issues the certificates of everyone in a cohort who met the attendance rule and has none yet (ADR-0030), on or
 * after the cohort's last day.
 */
final class IssueCohortCertificates
{
    public function __construct(private readonly IssueCertificate $issue) {}

    /**
     * @return int the certificates issued
     */
    public function handle(CourseCohort $cohort, User $actor): int
    {
        if ($cohort->isCancelled() || $cohort->ends_on > CarbonImmutable::today()) {
            throw ValidationException::withMessages(['cohort' => __('academy.validation.not_ended')]);
        }

        $issued = 0;
        foreach (AttendanceRule::eligible($cohort) as $enrolment) {
            /** @var Enrolment $enrolment */
            if ($enrolment->certificate()->exists()) {
                continue;
            }
            $this->issue->handle($enrolment, $actor);
            $issued++;
        }

        return $issued;
    }
}
