<?php

namespace App\Domain\Content\Enums;

/**
 * How serious an advisory is (PLAN.md §4, ADR-0034). Shown with its word and icon, never by colour alone.
 */
enum AdvisorySeverity: string
{
    case Info = 'info';
    case Advisory = 'advisory';
    case Warning = 'warning';

    public function label(?string $locale = null): string
    {
        return __("advisories.severity.{$this->value}", [], $locale);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Info => 'cyan',
            self::Advisory => 'saffron',
            self::Warning => 'ruby',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Info => 'info',
            self::Advisory => 'warning',
            self::Warning => 'danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $severity) {
            $options[$severity->value] = $severity->label();
        }

        return $options;
    }
}
