<?php

namespace App\Domain\Innovation\Mail;

use App\Domain\Innovation\Models\Startup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The Agency's decision on a startup listing (ADR-0020): listed, changes asked, declined or archived, with the
 * reviewer's note.
 */
final class StartupReviewedMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Startup $startup) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('innovation.mail.reviewed.subject', ['name' => $this->startup->name(), 'reference' => $this->startup->reference]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.innovation.reviewed', with: [
            'name' => $this->startup->founder_name,
            'startup' => $this->startup->name(),
            'status' => $this->startup->listing_status->label(),
            'note' => $this->startup->review_note,
            'reference' => $this->startup->reference,
        ]);
    }
}
