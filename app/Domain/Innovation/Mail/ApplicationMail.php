<?php

namespace App\Domain\Innovation\Mail;

use App\Domain\Innovation\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail to an applicant (ADR-0021): the receipt with the reference, or the decision with the note.
 */
final class ApplicationMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  'received'|'decided'|string  $kind
     */
    public function __construct(public Application $application, public string $kind) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __("innovation.mail.application.{$this->kind()}.subject", [
            'programme' => $this->application->programme->title($this->application->locale),
            'reference' => $this->application->reference,
        ]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.innovation.application', with: [
            'kind' => $this->kind(),
            'name' => $this->application->applicant_name,
            'programme' => $this->application->programme->title($this->application->locale),
            'stage' => $this->application->stage->label(),
            'note' => $this->kind() === 'decided' ? $this->application->decision_note : null,
            'reference' => $this->application->reference,
            'url' => route('applications.lookup', ['locale' => $this->application->locale, 'reference' => $this->application->reference]),
        ]);
    }

    private function kind(): string
    {
        return $this->kind === 'decided' ? 'decided' : 'received';
    }
}
