<?php

namespace App\Domain\Support\Actions;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Organisation\Models\Office;
use App\Domain\Support\Enums\TicketCategory;
use App\Domain\Support\Enums\TicketPriority;
use App\Domain\Support\Enums\TicketStatus;
use App\Domain\Support\Models\SlaPolicy;
use App\Domain\Support\Models\Ticket;
use App\Domain\Support\Sla\WorkingHours;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Staff change how a ticket is handled (ADR-0017): its status, priority, category, directorate and who works on it.
 * A new priority or category moves the targets (counted again from when the ticket came in); resolving stops the
 * clock and asks the requester to rate the help; reopening starts it again.
 */
final class UpdateTicket
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Ticket $ticket, array $data, User $staff): Ticket
    {
        $errors = [];

        $status = array_key_exists('status', $data) ? TicketStatus::tryFrom((string) $data['status']) : $ticket->status;
        $priority = array_key_exists('priority', $data) ? TicketPriority::tryFrom((string) $data['priority']) : $ticket->priority;
        $category = array_key_exists('category', $data) ? TicketCategory::tryFrom((string) $data['category']) : $ticket->category;
        if ($status === null) {
            $errors['data.status'] = __('support.validation.status');
        }
        if ($priority === null) {
            $errors['data.priority'] = __('support.validation.priority');
        }
        if ($category === null) {
            $errors['data.category'] = __('support.validation.category');
        }

        $officeId = $ticket->office_id;
        if (array_key_exists('office_id', $data)) {
            $officeId = filled($data['office_id']) && Office::query()->whereKey((string) $data['office_id'])->exists() ? (string) $data['office_id'] : null;
            if ($officeId !== $ticket->office_id && ! OfficeScope::covers($staff, $officeId, 'tickets.assign')) {
                $errors['data.office_id'] = __('support.validation.office_scope');
            }
        }

        $assigneeId = $ticket->assignee_id;
        if (array_key_exists('assignee_id', $data)) {
            $assigneeId = filled($data['assignee_id']) ? (int) $data['assignee_id'] : null;
            if ($assigneeId !== $ticket->assignee_id) {
                $assignee = $assigneeId !== null ? User::query()->find($assigneeId) : null;
                if (! $staff->can('tickets.assign') && $assigneeId !== $staff->id) {
                    $errors['data.assignee_id'] = __('support.validation.assign');
                } elseif ($assigneeId !== null && ($assignee === null || ! OfficeScope::covers($assignee, $officeId, 'tickets.reply'))) {
                    $errors['data.assignee_id'] = __('support.validation.assignee');
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $wasOpen = $ticket->status->isOpen();
        $ticket->office_id = $officeId;
        $ticket->assignee_id = $assigneeId;

        if ($priority !== $ticket->priority || $category !== $ticket->category) {
            $targets = SlaPolicy::targets($category, $priority);
            $ticket->response_due_at = WorkingHours::add($ticket->created_at, $targets['response']);
            $ticket->resolve_due_at = WorkingHours::add($ticket->created_at, $targets['resolve']);
        }
        $ticket->priority = $priority;
        $ticket->category = $category;

        if ($status !== $ticket->status) {
            $now = CarbonImmutable::now();
            if ($status === TicketStatus::Resolved) {
                $ticket->resolved_at = $now;
            } elseif ($status === TicketStatus::Closed) {
                $ticket->resolved_at ??= $now;
                $ticket->closed_at = $now;
            } else {
                $ticket->resolved_at = null;
                $ticket->closed_at = null;
            }
            $ticket->status = $status;
        }
        $ticket->save();

        if ($wasOpen && $ticket->status === TicketStatus::Resolved) {
            ReplyToTicket::tellRequester($ticket, 'resolved');
        }

        return $ticket;
    }
}
