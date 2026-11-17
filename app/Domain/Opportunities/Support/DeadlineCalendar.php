<?php

namespace App\Domain\Opportunities\Support;

use App\Domain\Events\Support\IcsCalendar;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Opportunities\Models\OpportunityTranslation;
use Carbon\CarbonImmutable;

/**
 * An opportunity's deadline (and, for a tender, the bid opening) as an iCalendar file (RFC 5545), so applicants can
 * add it to any calendar (ADR-0032). The UID stays the same when the deadline is extended, so a calendar that
 * imports the file again moves the entry.
 */
final class DeadlineCalendar
{
    public static function render(Opportunity $opportunity, OpportunityTranslation $translation): string
    {
        $url = route('opportunities.show', ['locale' => $translation->locale, 'slug' => $translation->slug]);
        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $stamp = CarbonImmutable::now('UTC')->format('Ymd\THis\Z');
        $at = fn (CarbonImmutable $date): string => $date->utc()->format('Ymd\THis\Z');

        $events = [[
            'uid' => 'opportunity-'.$opportunity->id.'-deadline-'.$translation->locale.'@'.$host,
            'summary' => __('opportunities.calendar.deadline', ['title' => $translation->title], $translation->locale),
            'at' => $opportunity->closes_at,
        ]];
        if ($opportunity->bid_opening_at !== null) {
            $events[] = [
                'uid' => 'opportunity-'.$opportunity->id.'-bids-'.$translation->locale.'@'.$host,
                'summary' => __('opportunities.calendar.bid_opening', ['title' => $translation->title], $translation->locale),
                'at' => $opportunity->bid_opening_at,
            ];
        }

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Harari Innovation and Technology Agency//Opportunities//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.IcsCalendar::escape($translation->title),
        ];
        foreach ($events as $event) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.$event['uid'];
            $lines[] = 'DTSTAMP:'.$stamp;
            $lines[] = 'DTSTART:'.$at($event['at']);
            $lines[] = 'DTEND:'.$at($event['at']->addMinutes(30));
            $lines[] = 'SUMMARY:'.IcsCalendar::escape($event['summary']);
            $lines[] = 'URL:'.$url;
            $lines[] = 'END:VEVENT';
        }
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(IcsCalendar::fold(...), $lines))."\r\n";
    }
}
