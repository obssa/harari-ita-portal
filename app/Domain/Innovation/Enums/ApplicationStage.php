<?php

namespace App\Domain\Innovation\Enums;

/**
 * Where an application stands (PLAN.md §5; ADR-0021): received, screening, interview, then a decision (accepted,
 * waitlisted, declined), or withdrawn by the applicant.
 */
enum ApplicationStage: string
{
    case Received = 'received';
    case Screening = 'screening';
    case Interview = 'interview';
    case Accepted = 'accepted';
    case Waitlisted = 'waitlisted';
    case Declined = 'declined';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return __("innovation.application_stage.{$this->value}");
    }

    public function isDecision(): bool
    {
        return in_array($this, [self::Accepted, self::Waitlisted, self::Declined], true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Received, self::Screening, self::Interview], true);
    }

    public function color(): string
    {
        return match ($this) {
            self::Received => 'warning',
            self::Screening, self::Interview => 'info',
            self::Accepted => 'success',
            self::Waitlisted => 'gray',
            self::Declined, self::Withdrawn => 'danger',
        };
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
