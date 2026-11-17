<?php

namespace App\Domain\Academy\Actions;

use App\Domain\Academy\Enums\CourseMode;
use App\Domain\Academy\Models\Course;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\CourseSession;
use App\Domain\Academy\Models\Instructor;
use App\Domain\Organisation\Models\Office;
use App\Domain\Status\Support\LocalisedText;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Saves a cohort with its sessions and instructors (ADR-0028). It must end on or after the day it starts, and an
 * enrolment window must close after it opens; each session must end after it starts. A session that already has
 * attendance is kept even when it is removed from the form. Raising the places moves learners up from the waiting
 * list.
 */
final class SaveCohort
{
    public const MAX_SESSIONS = 60;

    public function __construct(private readonly FillCohortFromWaitlist $waitlist) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(CourseCohort $cohort, array $data, User $actor): CourseCohort
    {
        $course = $cohort->exists ? $cohort->loadMissing('course')->course : Course::query()->find($data['course_id'] ?? null);
        $errors = [];
        if (! $course instanceof Course) {
            throw ValidationException::withMessages(['data.course_id' => __('academy.validation.course')]);
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['data.name'] = __('academy.validation.cohort_name');
        }
        $starts = $this->date($data['starts_on'] ?? null, 'data.starts_on', $errors, required: true);
        $ends = $this->date($data['ends_on'] ?? null, 'data.ends_on', $errors, required: true);
        if ($starts !== null && $ends !== null && $ends->lessThan($starts)) {
            $errors['data.ends_on'] = __('academy.validation.cohort_dates');
        }
        $opens = $this->date($data['enrol_opens_at'] ?? null, 'data.enrol_opens_at', $errors, time: true);
        $closes = $this->date($data['enrol_closes_at'] ?? null, 'data.enrol_closes_at', $errors, time: true);
        if ($opens !== null && $closes !== null && $closes <= $opens) {
            $errors['data.enrol_closes_at'] = __('academy.validation.window');
        }

        $sessions = [];
        foreach (array_slice(array_values((array) ($data['sessions'] ?? [])), 0, self::MAX_SESSIONS) as $i => $row) {
            $from = $this->date($row['starts_at'] ?? null, "data.sessions.{$i}.starts_at", $errors, required: true, time: true);
            $to = $this->date($row['ends_at'] ?? null, "data.sessions.{$i}.ends_at", $errors, required: true, time: true);
            if ($from !== null && $to !== null && $to <= $from) {
                $errors["data.sessions.{$i}.ends_at"] = __('academy.validation.session_times');
            }
            if ($from !== null && $to !== null && $to > $from) {
                $sessions[] = [
                    'id' => filled($row['id'] ?? null) ? (int) $row['id'] : null,
                    'starts_at' => $from,
                    'ends_at' => $to,
                    'titles' => LocalisedText::clean($row['titles'] ?? [], 160) ?: null,
                ];
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $office = filled($data['office_id'] ?? null) && Office::query()->whereKey($data['office_id'])->exists() ? (string) $data['office_id'] : null;
        $instructors = Instructor::query()->whereIn('id', array_map('intval', (array) ($data['instructors'] ?? [])))->pluck('id')->all();
        $capacity = filled($data['capacity'] ?? null) ? min(10000, max(1, (int) $data['capacity'])) : null;

        $saved = DB::transaction(function () use ($cohort, $course, $data, $name, $starts, $ends, $opens, $closes, $sessions, $office, $instructors, $capacity): CourseCohort {
            $cohort->fill([
                'name' => $name,
                'mode' => CourseMode::tryFrom((string) ($data['mode'] ?? '')) ?? $course->mode,
                'office_id' => $office,
                'venue_notes' => LocalisedText::clean($data['venue_notes'] ?? [], 300) ?: null,
                'starts_on' => $starts,
                'ends_on' => $ends,
                'enrol_opens_at' => $opens,
                'enrol_closes_at' => $closes,
                'capacity' => $capacity,
                'waitlist' => (bool) ($data['waitlist'] ?? true),
            ]);
            $cohort->course()->associate($course);
            $cohort->save();
            $cohort->instructors()->sync($instructors);

            $kept = [];
            foreach ($sessions as $row) {
                $session = $row['id'] !== null ? $cohort->sessions()->whereKey($row['id'])->first() : null;
                $session ??= new CourseSession;
                $session->fill(['starts_at' => $row['starts_at'], 'ends_at' => $row['ends_at'], 'titles' => $row['titles']]);
                $cohort->sessions()->save($session);
                $kept[] = $session->id;
            }
            $cohort->sessions()->whereNotIn('id', $kept)->whereDoesntHave('attendances')->get()->each(fn (CourseSession $session) => $session->delete());

            return $cohort->refresh();
        });

        $this->waitlist->handle($saved);

        return $saved;
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    private function date(mixed $value, string $field, array &$errors, bool $required = false, bool $time = false): ?CarbonImmutable
    {
        if (blank($value)) {
            if ($required) {
                $errors[$field] = __('academy.validation.date');
            }

            return null;
        }
        try {
            $date = CarbonImmutable::parse((string) $value);
        } catch (Throwable) {
            $errors[$field] = __('academy.validation.date');

            return null;
        }

        return $time ? $date : $date->startOfDay();
    }
}
