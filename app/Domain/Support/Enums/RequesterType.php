<?php

namespace App\Domain\Support\Enums;

/**
 * Who is asking: a public office or a resident (PLAN.md §4).
 */
enum RequesterType: string
{
    case PublicOffice = 'public_office';
    case Resident = 'resident';

    public function label(): string
    {
        return __("support.requester.{$this->value}");
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
