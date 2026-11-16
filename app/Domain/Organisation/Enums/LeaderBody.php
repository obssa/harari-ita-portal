<?php

namespace App\Domain\Organisation\Enums;

/**
 * The groups the leadership page is arranged in (PLAN.md §3 About:
 * leadership): the Agency's management, the directors of its directorates
 * and the leads of its five pillars.
 */
enum LeaderBody: string
{
    case Management = 'management';
    case Directors = 'directors';
    case Pillars = 'pillars';

    /**
     * The order of the leadership page: management first; its first person
     * by rank opens the page as the head of the Agency.
     *
     * @return list<self>
     */
    public static function displayOrder(): array
    {
        return [self::Management, self::Directors, self::Pillars];
    }

    public function description(?string $locale = null): string
    {
        return __("organisation.leader_body_desc.{$this->value}", [], $locale);
    }

    public function label(?string $locale = null): string
    {
        return __("organisation.leader_body.{$this->value}", [], $locale);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
