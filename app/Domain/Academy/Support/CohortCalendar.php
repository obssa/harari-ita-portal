<?php

namespace App\Domain\Academy\Support;

use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\CourseTranslation;
use App\Domain\Events\Support\IcsCalendar;
use App\Domain\Organisation\Queries\PublicDirectory;
use Carbon\CarbonImmutable;

/**
 * A cohort's sessions as an iCalendar file (RFC 5545), one event per session, so learners can add them to any
 * calendar (ADR-0031). Null while the course is not shown in that language or the cohort has no sessions.
 */
final class CohortCalendar
{
    public static function render(CourseCohort $cohort, string $locale): ?string
    {
        $cohort->loadMissing(['course.translations', 'sessions', 'office.translations']);
        $translation = PublicDirectory::shown($cohort->course, $locale);
        if (! $translation instanceof CourseTranslation || $cohort->sessions->isEmpty()) {
            return null;
        }

        $url = route('courses.show', ['locale' => $translation->locale, 'slug' => $translation->slug]);
        $location = trim(implode(', ', array_filter([$cohort->office?->name($locale), $cohort->venueNote($locale)])));
        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $stamp = CarbonImmutable::now('UTC')->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Harari Innovation and Technology Agency//Academy//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.IcsCalendar::escape($translation->title),
        ];
        foreach ($cohort->sessions as $session) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:session-'.$session->id.'-'.$translation->locale.'@'.$host;
            $lines[] = 'DTSTAMP:'.$stamp;
            $lines[] = 'DTSTART:'.$session->starts_at->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTEND:'.$session->ends_at->utc()->format('Ymd\THis\Z');
            $lines[] = 'SUMMARY:'.IcsCalendar::escape(trim($translation->title.' · '.$session->title($locale), ' ·'));
            if ($location !== '') {
                $lines[] = 'LOCATION:'.IcsCalendar::escape($location);
            }
            $lines[] = 'URL:'.$url;
            $lines[] = 'END:VEVENT';
        }
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(IcsCalendar::fold(...), $lines))."\r\n";
    }
}
