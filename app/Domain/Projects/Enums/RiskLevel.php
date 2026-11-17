<?php

namespace App\Domain\Projects\Enums;

/**
 * The likelihood or the impact of a project risk (ADR-0024).
 */
enum RiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return __("projects.risk.level.{$this->value}");
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
        };
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
