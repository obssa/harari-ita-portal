<?php

namespace App\Domain\Support\Actions;

use App\Domain\Support\Enums\TicketStatus;
use App\Domain\Support\Mail\TicketUpdatedMail;
use App\Domain\Support\Models\Ticket;
use App\Domain\Support\Models\TicketMessage;
use App\Models\User;
use App\Services\Sms\SendSms;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Staff answer a ticket (ADR-0017): a reply the requester sees (by email, and on the tracking page), or an internal
 * note. The first reply stops the response clock; a reply moves a new ticket into progress, or waits for the
 * requester when staff ask for something.
 */
final class ReplyToTicket
{
    public function __construct(private readonly StoreAttachments $attachments) {}

    /**
     * @param  list<UploadedFile>  $uploads
     */
    public function handle(Ticket $ticket, User $staff, string $body, bool $internal = false, bool $waitForRequester = false, array $uploads = []): TicketMessage
    {
        $body = trim($body);
        $errors = [];
        if (mb_strlen($body) < 2 || mb_strlen($body) > 5000) {
            $errors['body'] = __('support.validation.reply');
        }
        if (! $ticket->status->isOpen() && ! $internal) {
            $errors['body'] = __('support.validation.closed');
        }
        foreach ($uploads as $upload) {
            if (($problem = $this->attachments->problem($upload)) !== null) {
                $errors['files'] = $problem;
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $message = DB::transaction(function () use ($ticket, $staff, $body, $internal, $waitForRequester, $uploads): TicketMessage {
            $message = $ticket->messages()->create([
                'author_id' => $staff->id,
                'internal' => $internal,
                'body' => $body,
                'files' => $this->attachments->store($ticket->id, $uploads) ?: null,
            ]);

            if (! $internal) {
                $ticket->first_response_at ??= CarbonImmutable::now();
                $ticket->status = $waitForRequester ? TicketStatus::Waiting : TicketStatus::InProgress;
                $ticket->assignee_id ??= $staff->id;
                $ticket->save();
            }

            return $message;
        });

        if (! $internal) {
            self::tellRequester($ticket, 'reply');
        }

        return $message;
    }

    /** Lets the requester know by email, otherwise by SMS, that there is news on the ticket. */
    public static function tellRequester(Ticket $ticket, string $kind): void
    {
        if ($ticket->email !== null) {
            Mail::to($ticket->email)->locale($ticket->locale)->queue(new TicketUpdatedMail($ticket, $kind));
        } elseif ($ticket->phone !== null) {
            SendSms::dispatch($ticket->phone, __("support.sms.{$kind}", ['reference' => $ticket->reference], $ticket->locale));
        }
    }
}
