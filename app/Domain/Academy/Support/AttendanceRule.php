<?php

namespace App\Domain\Academy\Support;

use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\Attendance;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\Enrolment;

/**
 * Who has attended enough for a certificate (ADR-0030): a learner meets the rule when they attended at least the
 * course's share of the cohort's sessions (80% unless the course says otherwise). A cohort without sessions has no
 * rule to meet, so its certificates are issued one by one.
 */
final class AttendanceRule
{
    public static function meets(int $required, int $sessions, int $attended): bool
    {
        return $sessions > 0 && $attended * 100 >= $required * $sessions;
    }

    public static function percent(int $sessions, int $attended): ?int
    {
        return $sessions === 0 ? null : (int) floor($attended * 100 / $sessions);
    }

    /**
     * Sessions attended per enrolled learner of a cohort, in one query.
     *
     * @return array<string, int> enrolment id => sessions attended
     */
    public static function attendedCounts(CourseCohort $cohort): array
    {
        /** @var array<string, int> */
        return Attendance::query()
            ->whereIn('course_session_id', $cohort->sessions()->select('id'))
            ->selectRaw('enrolment_id, count(*) as attended')
            ->groupBy('enrolment_id')
            ->pluck('attended', 'enrolment_id')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();
    }

    /**
     * The enrolled learners who meet the rule.
     *
     * @return list<Enrolment>
     */
    public static function eligible(CourseCohort $cohort): array
    {
        $sessions = $cohort->sessions()->count();
        $counts = self::attendedCounts($cohort);
        $required = $cohort->loadMissing('course')->course->attendance_required;

        return $cohort->enrolments()->where('status', EnrolmentStatus::Enrolled->value)->whereNull('erased_at')
            ->orderBy('created_at')->get()
            ->filter(fn (Enrolment $enrolment): bool => self::meets($required, $sessions, $counts[$enrolment->id] ?? 0))
            ->values()->all();
    }
}
