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
