<?php

namespace App\Domain\Volunteers\Support;

/**
 * What volunteers can offer (ADR-0048). Keys are stored; labels come from
 * the language files.
 */
final class VolunteerOptions
{
    public const SKILLS = ['mentoring', 'software', 'web_design', 'data', 'networks', 'cyber_awareness', 'digital_literacy', 'business', 'it_support', 'event_support', 'photo_video', 'social_media', 'writing', 'translation'];

    public const AVAILABILITY = ['weekdays', 'evenings', 'weekends', 'events'];

    /** The languages of the region's people, Harari first. */
    public const LANGUAGES = ['har', 'am', 'om', 'so', 'en', 'ar'];

    /**
     * @return array<string, string>
     */
    public static function skills(): array
    {
        return self::labels(self::SKILLS, 'volunteers.skill');
    }

    /**
     * @return array<string, string>
     */
    public static function availability(): array
    {
        return self::labels(self::AVAILABILITY, 'volunteers.availability');
    }

    /**
     * @return array<string, string>
     */
    public static function languages(): array
    {
        return self::labels(self::LANGUAGES, 'volunteers.language');
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, string>
     */
    private static function labels(array $keys, string $prefix): array
    {
        return array_combine($keys, array_map(fn (string $key): string => __("{$prefix}.{$key}"), $keys));
    }
}
