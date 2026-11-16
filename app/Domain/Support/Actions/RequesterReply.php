<?php

namespace App\Domain\Support\Actions;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Notifications\Support\Recipients;
use App\Domain\Support\Enums\TicketStatus;
use App\Domain\Support\Models\Ticket;
use App\Domain\Support\Models\TicketMessage;
use App\Domain\Support\Notifications\NewSupportTicketNotice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The requester writes back from the tracking page (ADR-0017), and may rate the help once the ticket is resolved.
 * Writing back on a ticket that waits for them, or that was resolved, puts it back in progress.
 */
final class RequesterReply
{
    public function __construct(private readonly StoreAttachments $attachments) {}

    /**
     * @param  list<UploadedFile>  $uploads
     */
    public function message(Ticket $ticket, string $body, array $uploads = []): TicketMessage
    {
        $body = trim($body);
        $errors = [];
        if ($ticket->status === TicketStatus::Closed) {
            $errors['body'] = __('support.validation.closed');
        }
        if (mb_strlen($body) < 2 || mb_strlen($body) > 5000) {
            $errors['body'] = __('support.validation.reply');
        }
        if (count($uploads) > StoreAttachments::MAX_FILES) {
            $errors['files'] = __('support.validation.file_count', ['max' => StoreAttachments::MAX_FILES]);
        }
        foreach ($uploads as $upload) {
            if (($problem = $this->attachments->problem($upload)) !== null) {
                $errors['files'] = $problem;
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $message = DB::transaction(function () use ($ticket, $body, $uploads): TicketMessage {
            $message = $ticket->messages()->create([
                'from_requester' => true,
                'body' => $body,
                'files' => $this->attachments->store($ticket->id, $uploads) ?: null,
            ]);
            if (in_array($ticket->status, [TicketStatus::Waiting, TicketStatus::Resolved], true)) {
                $ticket->status = TicketStatus::InProgress;
                $ticket->resolved_at = null;
                $ticket->save();
            }

            return $message;
        });

        $handlers = $ticket->assignee !== null
            ? collect([$ticket->assignee])
            : Recipients::withPermission('tickets.view')->filter(fn (User $user): bool => OfficeScope::covers($user, $ticket->office_id, 'tickets.view'));
        Recipients::notify($handlers, NewSupportTicketNotice::for($ticket, reply: true));

        return $message;
    }

    public function rate(Ticket $ticket, int $score, ?string $note): Ticket
    {
        if ($ticket->status !== TicketStatus::Resolved) {
            throw ValidationException::withMessages(['satisfaction' => __('support.validation.rate_resolved')]);
        }
        if ($score < 1 || $score > 5) {
            throw ValidationException::withMessages(['satisfaction' => __('support.validation.rating')]);
        }

        $note = trim((string) $note);
        $ticket->satisfaction = $score;
        $ticket->satisfaction_note = $note !== '' ? mb_substr($note, 0, 1000) : null;
        $ticket->status = TicketStatus::Closed;
        $ticket->closed_at = CarbonImmutable::now();
        $ticket->save();

        return $ticket;
    }
}
