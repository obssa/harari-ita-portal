<?php

namespace App\Domain\Academy\Actions;

use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\Attendance;
use App\Domain\Academy\Models\CourseSession;
use App\Domain\Academy\Support\CohortScope;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records who was at a session, in one save (ADR-0030): the learners ticked are present, the others are not. Only
 * enrolled learners of the session's cohort count; a session can be marked from the day it takes place.
 */
final class MarkAttendance
{
    /**
     * @param  list<string>  $present  enrolment ids
     * @return int the learners present
     */
    public function handle(CourseSession $session, array $present, User $actor): int
    {
        $cohort = $session->loadMissing('cohort')->cohort;
        if (! $actor->can('cohorts.attendance') || ! CohortScope::covers($actor, $cohort)) {
            throw new AuthorizationException;
        }
        if ($session->starts_at->startOfDay() > CarbonImmutable::now()) {
            throw ValidationException::withMessages(['present' => __('academy.validation.session_future')]);
        }

        $ids = $cohort->enrolments()->where('status', EnrolmentStatus::Enrolled->value)->whereNull('erased_at')
            ->whereIn('id', array_map('strval', $present))->pluck('id')->all();

        DB::transaction(function () use ($session, $ids, $actor): void {
            $session->attendances()->whereNotIn('enrolment_id', $ids)->get()->each(fn (Attendance $row) => $row->delete());
            $already = $session->attendances()->pluck('enrolment_id')->all();
            foreach (array_diff($ids, $already) as $id) {
                $session->attendances()->create(['enrolment_id' => $id, 'marked_by' => $actor->id]);
            }
        });

        return count($ids);
    }
}
