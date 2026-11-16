<?php

namespace App\Domain\Innovation\Enums;

/**
 * What a programme is (ADR-0021).
 */
enum ProgrammeKind: string
{
    case Incubation = 'incubation';
    case Acceleration = 'acceleration';
    case Training = 'training';
    case Other = 'other';

    public function label(): string
    {
        return __("innovation.kind.{$this->value}");
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
