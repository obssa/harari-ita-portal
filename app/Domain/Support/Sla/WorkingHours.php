<?php

namespace App\Domain\Support\Sla;

use App\Services\Calendar\EthiopianCalendar;
use Carbon\CarbonImmutable;

/**
 * The SLA clock (ADR-0017): adds and counts working hours, Monday to Friday between the configured start and end
 * of the day in Addis Ababa. A ticket filed in the evening or at the weekend starts counting at the next opening.
 * Public holidays are not counted out yet (as for the forms inbox).
 */
final class WorkingHours
{
    public static function add(CarbonImmutable $from, int $hours): CarbonImmutable
    {
        $minutes = max(0, $hours) * 60;
        $at = self::nextOpen($from->setTimezone(EthiopianCalendar::timezone()));

        while ($minutes > 0) {
            $close = self::close($at);
            $available = (int) $at->diffInMinutes($close, absolute: true);
            if ($minutes <= $available) {
                return $at->addMinutes($minutes)->utc();
            }
            $minutes -= $available;
            $at = self::nextOpen($close->addMinute());
        }

        return $at->utc();
    }

    /** Working minutes between two moments (0 when the end is not after the start). */
    public static function minutesBetween(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $at = self::nextOpen($from->setTimezone(EthiopianCalendar::timezone()));
        $end = $to->setTimezone(EthiopianCalendar::timezone());
        $total = 0;

        while ($at < $end) {
            $close = self::close($at);
            $until = $end < $close ? $end : $close;
            $total += (int) $at->diffInMinutes($until, absolute: true);
            $at = self::nextOpen($close->addMinute());
        }

        return $total;
    }

    private static function nextOpen(CarbonImmutable $at): CarbonImmutable
    {
        for ($i = 0; $i < 8; $i++) {
            if (! $at->isWeekend()) {
                $open = self::open($at);
                if ($at < $open) {
                    return $open;
                }
                if ($at < self::close($at)) {
                    return $at;
                }
            }
            $at = self::open($at->addDay());
        }

        return $at;
    }

    private static function open(CarbonImmutable $day): CarbonImmutable
    {
        [$hour, $minute] = self::time((string) config('portal.support.day_starts', '08:30'), [8, 30]);

        return $day->setTime($hour, $minute);
    }

    private static function close(CarbonImmutable $day): CarbonImmutable
    {
        [$hour, $minute] = self::time((string) config('portal.support.day_ends', '17:30'), [17, 30]);

        return $day->setTime($hour, $minute);
    }

    /**
     * @param  array{0: int, 1: int}  $default
     * @return array{0: int, 1: int}
     */
    private static function time(string $value, array $default): array
    {
        return preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $value, $m) === 1 ? [(int) $m[1], (int) $m[2]] : $default;
    }
}
