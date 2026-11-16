<?php

namespace App\Domain\Status\Enums;

/**
 * The state of a system on the status page (PLAN.md §4; ADR-0018), from best to worst. An incident's impact is the
 * state it puts its systems in.
 */
enum ComponentState: string
{
    case Operational = 'operational';
    case Maintenance = 'maintenance';
    case Degraded = 'degraded';
    case PartialOutage = 'partial_outage';
    case MajorOutage = 'major_outage';

    public function label(): string
    {
        return __("status.state.{$this->value}");
    }

    public function severity(): int
    {
        return match ($this) {
            self::Operational => 0,
            self::Maintenance => 1,
            self::Degraded => 2,
            self::PartialOutage => 3,
            self::MajorOutage => 4,
        };
    }

    /** The x-ui.status-pill state. */
    public function pill(): string
    {
        return match ($this) {
            self::Operational => 'ok',
            self::Maintenance => 'maintenance',
            self::Degraded => 'degraded',
            self::PartialOutage, self::MajorOutage => 'down',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Operational => 'success',
            self::Maintenance => 'info',
            self::Degraded => 'warning',
            self::PartialOutage, self::MajorOutage => 'danger',
        };
    }

    /**
     * The impacts an incident can have.
     *
     * @return array<string, string>
     */
    public static function impactOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            if ($case !== self::Operational) {
                $options[$case->value] = $case->label();
            }
        }

        return $options;
    }
}
