<?php

namespace App\Domain\Support\Mail;

use App\Domain\Support\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The receipt for a support request (ADR-0017), in the requester's language: the reference, when to expect a
 * first answer and where to follow the request.
 */
final class TicketReceivedMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('support.mail.received.subject', ['reference' => $this->ticket->reference]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.support.received', with: [
            'name' => $this->ticket->name,
            'reference' => $this->ticket->reference,
            'due' => $this->ticket->response_due_at,
            'url' => route('support.track', ['locale' => $this->ticket->locale, 'reference' => $this->ticket->reference]),
        ]);
    }
}
