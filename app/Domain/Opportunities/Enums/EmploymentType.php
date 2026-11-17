<?php

namespace App\Domain\Opportunities\Enums;

/**
 * The kind of employment a vacancy offers, with its schema.org JobPosting value (ADR-0032).
 */
enum EmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Temporary = 'temporary';

    public function label(): string
    {
        return __("opportunities.employment.{$this->value}");
    }

    public function schemaOrg(): string
    {
        return match ($this) {
            self::FullTime => 'FULL_TIME',
            self::PartTime => 'PART_TIME',
            self::Contract => 'CONTRACTOR',
            self::Temporary => 'TEMPORARY',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
