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
