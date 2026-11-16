<?php

namespace App\Domain\Innovation\Enums;

/**
 * Where a startup's listing stands (ADR-0020). Only listed startups are public.
 */
enum ListingStatus: string
{
    case Submitted = 'submitted';
    case ChangesRequested = 'changes_requested';
    case Listed = 'listed';
    case Declined = 'declined';
    case Archived = 'archived';

    public function label(): string
    {
        return __("innovation.listing.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Submitted => 'warning',
            self::ChangesRequested => 'info',
            self::Listed => 'success',
            self::Declined, self::Archived => 'gray',
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
