<?php

namespace App\Domain\Engagement\Enums;

/**
 * A campaign is written as a draft, may wait for its time, is sent in
 * batches and ends sent or cancelled (ADR-0052).
 */
enum CampaignStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Sending = 'sending';
    case Sent = 'sent';
    case Cancelled = 'cancelled';

    /**
     * The moves a campaign may make. Sent and Cancelled are final; a scheduled campaign may be moved to another time.
     */
    public function canMoveTo(self $to): bool
    {
        return in_array($to, match ($this) {
            self::Draft => [self::Scheduled, self::Sending],
            self::Scheduled => [self::Draft, self::Scheduled, self::Sending],
            self::Sending => [self::Sent, self::Cancelled],
            self::Sent, self::Cancelled => [],
        }, true);
    }

    public function label(): string
    {
        return __("campaigns.status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Scheduled => 'warning',
            self::Sending => 'info',
            self::Sent => 'success',
            self::Cancelled => 'danger',
        };
    }
}
