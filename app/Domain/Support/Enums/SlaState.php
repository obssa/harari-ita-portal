<?php

namespace App\Domain\Support\Enums;

/**
 * A ticket against its target: on time, due soon (within two working hours), breached, or met.
 */
enum SlaState: string
{
    case OnTime = 'on_time';
    case DueSoon = 'due_soon';
    case Breached = 'breached';
    case Met = 'met';

    public function label(): string
    {
        return __("support.sla.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::OnTime, self::Met => 'success',
            self::DueSoon => 'warning',
            self::Breached => 'danger',
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
