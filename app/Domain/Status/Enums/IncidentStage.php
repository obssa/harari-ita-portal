<?php

namespace App\Domain\Status\Enums;

/**
 * Where an incident stands (ADR-0018); planned maintenance is "scheduled" until it starts.
 */
enum IncidentStage: string
{
    case Scheduled = 'scheduled';
    case Investigating = 'investigating';
    case Identified = 'identified';
    case Monitoring = 'monitoring';
    case Resolved = 'resolved';

    public function label(): string
    {
        return __("status.stage.{$this->value}");
    }

    /**
     * The stages an update may move the incident to from this one (ADR-0054). Scheduled is only where planned
     * maintenance starts and is never chosen again; the working stages move among themselves and to resolved; a
     * resolved incident can be reopened at a working stage. An update at the same stage is always allowed.
     *
     * @return list<self>
     */
    public function nextStages(): array
    {
        $working = [self::Investigating, self::Identified, self::Monitoring];

        return match ($this) {
            self::Scheduled => [...$working, self::Resolved],
            self::Investigating, self::Identified, self::Monitoring => array_values(array_filter(
                [...$working, self::Resolved],
                fn (self $stage): bool => $stage !== $this,
            )),
            self::Resolved => $working,
        };
    }

    public function canMoveTo(self $to): bool
    {
        return $to === $this || in_array($to, $this->nextStages(), true);
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
