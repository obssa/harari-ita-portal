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
 * News on a support request (ADR-0017): staff replied, or the request is resolved and can be rated. The message
 * itself is read on the tracking page, behind the reference and the contact, not in the email.
 */
final class TicketUpdatedMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  'reply'|'resolved'|string  $kind
     */
    public function __construct(public Ticket $ticket, public string $kind) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __("support.mail.{$this->kind()}.subject", ['reference' => $this->ticket->reference]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.support.updated', with: [
            'kind' => $this->kind(),
            'name' => $this->ticket->name,
            'reference' => $this->ticket->reference,
            'url' => route('support.track', ['locale' => $this->ticket->locale, 'reference' => $this->ticket->reference]),
        ]);
    }

    private function kind(): string
    {
        return $this->kind === 'resolved' ? 'resolved' : 'reply';
    }
}
