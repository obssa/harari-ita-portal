<?php

namespace App\Domain\Support\Enums;

/**
 * How a ticket came in: the website, or recorded by staff from a call, an email or a visit.
 */
enum TicketChannel: string
{
    case Web = 'web';
    case Phone = 'phone';
    case Email = 'email';
    case WalkIn = 'walk_in';

    public function label(): string
    {
        return __("support.channel.{$this->value}");
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
