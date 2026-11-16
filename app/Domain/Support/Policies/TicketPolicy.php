<?php

namespace App\Domain\Support\Policies;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Support\Models\Ticket;
use App\Models\User;

/**
 * Support tickets (PLAN.md §7; ADR-0017). `tickets.view` to read, `tickets.reply` to answer and record tickets taken
 * by phone or in person, `tickets.assign` to give tickets to colleagues or move them to another directorate. Staff
 * scoped to directorates see and handle only those directorates' tickets. Tickets are never deleted by hand: the
 * retention purge and privacy erasure remove them.
 */
final class TicketPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('tickets.view');
    }

    public function view(User $actor, Ticket $ticket): bool
    {
        return OfficeScope::covers($actor, $ticket->office_id, 'tickets.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('tickets.reply');
    }

    public function update(User $actor, Ticket $ticket): bool
    {
        return OfficeScope::covers($actor, $ticket->office_id, 'tickets.reply');
    }

    public function reply(User $actor, Ticket $ticket): bool
    {
        return $this->update($actor, $ticket);
    }

    public function assign(User $actor, Ticket $ticket): bool
    {
        return OfficeScope::covers($actor, $ticket->office_id, 'tickets.assign');
    }

    public function delete(User $actor, Ticket $ticket): bool
    {
        return false;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }

    public function forceDelete(User $actor, Ticket $ticket): bool
    {
        return false;
    }

    public function forceDeleteAny(User $actor): bool
    {
        return false;
    }

    public function restore(User $actor, Ticket $ticket): bool
    {
        return false;
    }

    public function restoreAny(User $actor): bool
    {
        return false;
    }

    public function replicate(User $actor, Ticket $ticket): bool
    {
        return false;
    }

    public function reorder(User $actor): bool
    {
        return false;
    }
}
