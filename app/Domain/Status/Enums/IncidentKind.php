<?php

namespace App\Domain\Status\Enums;

/**
 * An unplanned incident or planned maintenance (ADR-0018).
 */
enum IncidentKind: string
{
    case Incident = 'incident';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return __("status.kind.{$this->value}");
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [self::Incident->value => self::Incident->label(), self::Maintenance->value => self::Maintenance->label()];
    }
}
