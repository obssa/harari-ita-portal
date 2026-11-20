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

    /**
     * The decisions staff may take from this one (ADR-0054). A request under review can be listed, sent back for
     * changes or declined; one sent back returns to review once the founders reply; a listed startup can be sent back
     * or archived; an archived one can be listed again. Declined is final: the founders send a new request.
     *
     * @return list<self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Submitted => [self::ChangesRequested, self::Listed, self::Declined],
            self::ChangesRequested => [self::Submitted, self::Listed, self::Declined],
            self::Listed => [self::ChangesRequested, self::Archived],
            self::Archived => [self::Listed],
            self::Declined => [],
        };
    }

    public function canMoveTo(self $to): bool
    {
        return in_array($to, $this->nextStatuses(), true);
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
