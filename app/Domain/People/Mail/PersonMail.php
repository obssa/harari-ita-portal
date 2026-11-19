<?php

namespace App\Domain\People\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A plain message to a person the Agency deals with (ADR-0053): a staff member's own words to an applicant, a team
 * or a volunteer, a challenge's results, or a volunteer's receipt. The caller writes every line in the person's
 * language; the queued copy is encrypted because it carries their words and details.
 */
final class PersonMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $heading,
        public ?string $name,
        public string $body,
        public ?string $reference = null,
        public ?string $url = null,
        public ?string $button = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.people.message', with: [
            'heading' => $this->heading,
            'name' => $this->name,
            'body' => $this->body,
            'reference' => $this->reference,
            'url' => $this->url,
            'button' => $this->button,
        ]);
    }
}
