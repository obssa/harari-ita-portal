<?php

namespace App\Domain\Academy\Enums;

/**
 * Where an enrolment stands (ADR-0029). Attendance and the certificate are recorded on their own, so a learner who
 * finished a course stays "enrolled".
 */
enum EnrolmentStatus: string
{
    case Enrolled = 'enrolled';
    case Waitlisted = 'waitlisted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("academy.enrolment.status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Enrolled => 'success',
            self::Waitlisted => 'warning',
            self::Cancelled => 'gray',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Enrolled => 'success',
            self::Waitlisted => 'warning',
            self::Cancelled => 'neutral',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
