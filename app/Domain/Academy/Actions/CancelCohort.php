<?php

namespace App\Domain\Academy\Actions;

use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\Enrolment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cancels a cohort that has not ended (ADR-0028): enrolment stops, and every learner with a place or on the waiting
 * list is told, in their language.
 */
final class CancelCohort
{
    public function __construct(private readonly SendEnrolmentMessage $messages) {}

    /**
     * @return int the learners told
     */
    public function handle(CourseCohort $cohort): int
    {
        if ($cohort->isCancelled()) {
            return 0;
        }
        if ($cohort->hasEnded() || $cohort->certificates()->exists()) {
            throw ValidationException::withMessages(['cohort' => __('academy.validation.cancel_ended')]);
        }

        $told = DB::transaction(function () use ($cohort): array {
            $cohort->cancelled_at = CarbonImmutable::now();
            $cohort->save();

            $told = [];
            /** @var Enrolment $enrolment */
            foreach ($cohort->enrolments()->active()->whereNull('erased_at')->get() as $enrolment) {
                $enrolment->status = EnrolmentStatus::Cancelled;
                $enrolment->cancelled_at = CarbonImmutable::now();
                $enrolment->save();
                $told[] = $enrolment;
            }

            return $told;
        });

        foreach ($told as $enrolment) {
            $this->messages->handle($enrolment, SendEnrolmentMessage::COHORT_CANCELLED);
        }

        return count($told);
    }
}
