<?php

namespace App\Domain\Opportunities\Enums;

/**
 * The entries of an opportunity's log (ADR-0033): a change to the documents, a question with its answer, a new
 * deadline, or the cancellation with its reason.
 */
enum AddendumKind: string
{
    case Addendum = 'addendum';
    case Clarification = 'clarification';
    case Extension = 'extension';
    case Cancellation = 'cancellation';

    public function label(?string $locale = null): string
    {
        return __("opportunities.addendum.kind.{$this->value}", [], $locale);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Addendum => 'primary',
            self::Clarification => 'cyan',
            self::Extension => 'saffron',
            self::Cancellation => 'danger',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Addendum => 'primary',
            self::Clarification => 'info',
            self::Extension => 'warning',
            self::Cancellation => 'danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $kind) {
            $options[$kind->value] = $kind->label();
        }

        return $options;
    }
}
