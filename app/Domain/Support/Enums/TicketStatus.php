<?php

namespace App\Domain\Support\Enums;

/**
 * Where a support ticket stands (ADR-0017). New, in progress and waiting for the requester are open; resolved
 * waits for the requester's rating; closed is final.
 */
enum TicketStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return __("support.status.{$this->value}");
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::InProgress, self::Waiting], true);
    }

    /**
     * The statuses staff may set from this one (ADR-0054). Open statuses move among themselves and to resolved or
     * closed; a resolved ticket can be reopened or closed; closed is final (the requester then opens a new request).
     *
     * @return list<self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::New, self::InProgress, self::Waiting => array_values(array_filter(
                [self::InProgress, self::Waiting, self::Resolved, self::Closed],
                fn (self $status): bool => $status !== $this,
            )),
            self::Resolved => [self::InProgress, self::Closed],
            self::Closed => [],
        };
    }

    public function canMoveTo(self $to): bool
    {
        return in_array($to, $this->nextStatuses(), true);
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::InProgress => 'info',
            self::Waiting => 'gray',
            self::Resolved => 'success',
            self::Closed => 'gray',
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
