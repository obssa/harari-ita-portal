<?php

namespace App\Domain\Academy\Actions;

use App\Domain\Academy\Mail\EnrolmentMail;
use App\Domain\Academy\Models\CourseTranslation;
use App\Domain\Academy\Models\Enrolment;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Services\Calendar\EthiopianCalendar;
use App\Services\Sms\SendSms;
use Illuminate\Support\Facades\Mail;

/**
 * Tells a learner what happened, by email and by text message, in the language they enrolled in (ADR-0029; PLAN.md
 * §4: SMS-first audience). Every message carries the link to their enrolment page.
 */
final class SendEnrolmentMessage
{
    public const ENROLLED = 'enrolled';

    public const WAITLISTED = 'waitlisted';

    public const PROMOTED = 'promoted';

    public const REMINDER = 'reminder';

    public const CANCELLED = 'cancelled';

    /** The details were already enrolled: the same link again. */
    public const AGAIN = 'again';

    public const COHORT_CANCELLED = 'cohort_cancelled';

    public const CERTIFICATE = 'certificate';

    public const KINDS = [self::ENROLLED, self::WAITLISTED, self::PROMOTED, self::REMINDER, self::CANCELLED, self::AGAIN, self::COHORT_CANCELLED, self::CERTIFICATE];

    public function handle(Enrolment $enrolment, string $kind): void
    {
        if ($enrolment->erased_at !== null) {
            return;
        }
        $enrolment->loadMissing('cohort.course.translations');

        if ($enrolment->email !== null) {
            Mail::to($enrolment->email)->locale($enrolment->locale)->queue(new EnrolmentMail($enrolment, $kind));
        }

        if ($enrolment->phone !== null) {
            SendSms::dispatch($enrolment->phone, self::text($enrolment, $kind));
        }
    }

    /** The short message for a phone: what happened, the course, when it starts, and the link. */
    public static function text(Enrolment $enrolment, string $kind): string
    {
        $locale = $enrolment->locale;
        $enrolment->loadMissing('cohort.course.translations');

        return __("academy.sms.{$kind}", [
            'course' => self::courseTitle($enrolment),
            'date' => app(EthiopianCalendar::class)->format($enrolment->cohort->starts_on, $locale),
            'link' => self::learnerUrl($enrolment),
        ], $locale);
    }

    public static function courseTitle(Enrolment $enrolment): string
    {
        $course = $enrolment->loadMissing('cohort.course.translations')->cohort->course;
        $translation = PublicDirectory::shown($course, $enrolment->locale);

        return $translation instanceof CourseTranslation ? $translation->title : $course->title($enrolment->locale);
    }

    public static function learnerUrl(Enrolment $enrolment): string
    {
        return route('enrolments.show', ['locale' => $enrolment->locale, 'token' => (string) $enrolment->token]);
    }
}
