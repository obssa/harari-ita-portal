<?php

namespace App\Domain\Academy\Enums;

/**
 * How much a learner should already know before a course (ADR-0028).
 */
enum CourseLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    public function label(): string
    {
        return __("academy.level.{$this->value}");
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $level) {
            $options[$level->value] = $level->label();
        }

        return $options;
    }
}
