<?php

namespace App\Domain\Projects\Enums;

/**
 * Where a project risk stands (ADR-0024).
 */
enum RiskStatus: string
{
    case Open = 'open';
    case Mitigated = 'mitigated';
    case Closed = 'closed';

    public function label(): string
    {
        return __("projects.risk.status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'danger',
            self::Mitigated => 'warning',
            self::Closed => 'gray',
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
