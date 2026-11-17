<?php

namespace App\Domain\Projects\Enums;

/**
 * How a running project is doing (ADR-0024): on track, at risk or off track, set by its manager.
 */
enum RagStatus: string
{
    case Green = 'green';
    case Amber = 'amber';
    case Red = 'red';

    public function label(): string
    {
        return __("projects.rag.{$this->value}");
    }

    /** The badge tone on public pages. */
    public function tone(): string
    {
        return match ($this) {
            self::Green => 'success',
            self::Amber => 'warning',
            self::Red => 'danger',
        };
    }

    /** The colour in the admin. */
    public function color(): string
    {
        return match ($this) {
            self::Green => 'success',
            self::Amber => 'warning',
            self::Red => 'danger',
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
