<?php

namespace App\Domain\Organisation\Enums;

/**
 * Kinds of unit in the Agency's structure (PLAN.md §3 About: structure):
 * its directorates, the teams under them, its offices in the region and the
 * partner institutions it works through.
 */
enum OfficeType: string
{
    case Directorate = 'directorate';
    case Team = 'team';
    case RegionalOffice = 'regional_office';
    case Partner = 'partner';

    /**
     * The units people can turn to: their contacts are listed on the contact
     * page, and mentors and volunteers choose one to help.
     *
     * @return list<self>
     */
    public static function reachable(): array
    {
        return [self::Directorate, self::RegionalOffice];
    }

    /**
     * @return list<string>
     */
    public static function reachableValues(): array
    {
        return array_map(fn (self $type): string => $type->value, self::reachable());
    }

    public function label(?string $locale = null): string
    {
        return __("organisation.office_type.{$this->value}", [], $locale);
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
