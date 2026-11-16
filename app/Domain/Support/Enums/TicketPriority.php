<?php

namespace App\Domain\Support\Enums;

/**
 * How urgent a ticket is; with the category it sets the SLA targets (ADR-0017).
 */
enum TicketPriority: string
{
    case Urgent = 'urgent';
    case High = 'high';
    case Normal = 'normal';
    case Low = 'low';

    public function label(): string
    {
        return __("support.priority.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Urgent => 'danger',
            self::High => 'warning',
            self::Normal => 'info',
            self::Low => 'gray',
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
