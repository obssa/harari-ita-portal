<?php

namespace App\Domain\Academy\Actions;

use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\Enrolment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Gives free places to the waiting list, first come first served, and tells each learner moved up (ADR-0029). Runs
 * when a place is given back and when the places are raised, until the cohort ends.
 */
final class FillCohortFromWaitlist
{
    public function __construct(private readonly SendEnrolmentMessage $messages) {}

    /**
     * @return list<Enrolment> the learners moved up
     */
    public function handle(CourseCohort $cohort): array
    {
        $promoted = DB::transaction(function () use ($cohort): array {
            $locked = CourseCohort::query()->whereKey($cohort->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->isCancelled() || $locked->hasEnded()) {
                return [];
            }

            $free = $locked->capacity === null ? PHP_INT_MAX : $locked->capacity - $locked->enrolledCount();
            if ($free <= 0) {
                return [];
            }

            $moved = [];
            /** @var Enrolment $next */
            foreach ($locked->enrolments()->where('status', EnrolmentStatus::Waitlisted->value)->whereNull('erased_at')
                ->orderBy('created_at')->orderBy('id')->limit(min($free, 500))->get() as $next) {
                $next->status = EnrolmentStatus::Enrolled;
                $next->promoted_at = CarbonImmutable::now();
                $next->save();
                $moved[] = $next;
            }

            return $moved;
        });

        foreach ($promoted as $enrolment) {
            $this->messages->handle($enrolment, SendEnrolmentMessage::PROMOTED);
        }

        return $promoted;
    }
}
