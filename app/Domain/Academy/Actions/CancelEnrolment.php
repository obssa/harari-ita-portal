<?php

namespace App\Domain\Academy\Actions;

use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\Enrolment;
use Carbon\CarbonImmutable;

/**
 * Gives a place back, by the learner from their page or by staff (ADR-0029). A freed place goes to the first learner
 * on the waiting list. A learner who already has a certificate keeps their place.
 */
final class CancelEnrolment
{
    public function __construct(
        private readonly SendEnrolmentMessage $messages,
        private readonly FillCohortFromWaitlist $waitlist,
    ) {}

    public function handle(Enrolment $enrolment): bool
    {
        if ($enrolment->status === EnrolmentStatus::Cancelled || $enrolment->certificate()->exists()) {
            return false;
        }

        $freesPlace = $enrolment->status === EnrolmentStatus::Enrolled;
        $enrolment->status = EnrolmentStatus::Cancelled;
        $enrolment->cancelled_at = CarbonImmutable::now();
        $enrolment->save();

        $this->messages->handle($enrolment, SendEnrolmentMessage::CANCELLED);
        if ($freesPlace) {
            $this->waitlist->handle($enrolment->loadMissing('cohort')->cohort);
        }

        return true;
    }
}
