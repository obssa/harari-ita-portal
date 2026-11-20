<?php

namespace App\Domain\Volunteers\Enums;

/**
 * An assignment is planned, then done (with the hours given) or cancelled.
 */
enum AssignmentStatus: string
{
    case Planned = 'planned';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("volunteers.assignment.status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'info',
            self::Done => 'success',
            self::Cancelled => 'gray',
        };
    }

    /**
     * The statuses an existing assignment may take from this one (ADR-0054): a planned task is done or cancelled, and
     * a done or cancelled one can go back to planned to undo a mistake, never straight from cancelled to done (whose
     * hours count) or back. A new assignment may be recorded with any status.
     *
     * @return list<self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Planned => [self::Done, self::Cancelled],
            self::Done, self::Cancelled => [self::Planned],
        };
    }

    public function canMoveTo(self $to): bool
    {
        return in_array($to, $this->nextStatuses(), true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(array_map(fn (self $s): string => $s->value, self::cases()), array_map(fn (self $s): string => $s->label(), self::cases()));
    }
}
