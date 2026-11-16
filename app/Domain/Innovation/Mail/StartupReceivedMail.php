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
 * The receipt for a listing request (ADR-0020), with the reference to follow the review.
 */
final class StartupReceivedMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Startup $startup) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('innovation.mail.received.subject', ['reference' => $this->startup->reference]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.innovation.received', with: [
            'name' => $this->startup->founder_name,
            'startup' => $this->startup->name(),
            'reference' => $this->startup->reference,
            'url' => route('startups.listing', ['locale' => $this->locale ?? app()->getLocale(), 'reference' => $this->startup->reference]),
        ]);
    }
}
