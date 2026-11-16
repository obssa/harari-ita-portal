<?php

namespace App\Domain\Services\Enums;

/**
 * How someone can use a service (PLAN.md §4: the channel badges on service cards).
 */
enum ServiceChannel: string
{
    case Online = 'online';
    case InPerson = 'in_person';
    case Phone = 'phone';

    public function label(): string
    {
        return __("services.channel.{$this->value}");
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
