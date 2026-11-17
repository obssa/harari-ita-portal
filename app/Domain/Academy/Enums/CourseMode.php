<?php

namespace App\Domain\Academy\Enums;

/**
 * Where a course or cohort is taught (ADR-0028): in a room, online, or both.
 */
enum CourseMode: string
{
    case InPerson = 'in_person';
    case Online = 'online';
    case Blended = 'blended';

    public function label(): string
    {
        return __("academy.mode.{$this->value}");
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $mode) {
            $options[$mode->value] = $mode->label();
        }

        return $options;
    }
}
