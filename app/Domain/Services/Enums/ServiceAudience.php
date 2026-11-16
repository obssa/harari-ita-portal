<?php

namespace App\Domain\Services\Enums;

/**
 * Who a service is for (PLAN.md §4: the catalogue's audience filter).
 */
enum ServiceAudience: string
{
    case Residents = 'residents';
    case Businesses = 'businesses';
    case Government = 'government';
    case Startups = 'startups';

    public function label(): string
    {
        return __("services.audience.{$this->value}");
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
