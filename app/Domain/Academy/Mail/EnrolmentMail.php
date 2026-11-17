<?php

namespace App\Domain\Academy\Mail;

use App\Domain\Academy\Actions\SendEnrolmentMessage;
use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\Enrolment;
use App\Domain\Academy\Support\CohortCalendar;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The email for each step of an enrolment (ADR-0029): enrolled, on the waiting list, moved up, the reminder the day
 * before, a place given back, the cohort cancelled, and the certificate issued. A confirmed place carries the
 * cohort's sessions as a calendar file.
 */
final class EnrolmentMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Enrolment $enrolment,
        public string $kind,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __("academy.mail.{$this->kind}.subject", ['course' => SendEnrolmentMessage::courseTitle($this->enrolment)]));
    }

    public function content(): Content
    {
        $this->enrolment->loadMissing(['cohort.course.translations', 'cohort.sessions', 'cohort.office.translations']);

        return new Content(markdown: 'mail.academy.enrolment', with: [
            'kind' => $this->kind,
            'name' => $this->enrolment->name,
            'cohort' => $this->enrolment->cohort,
            'title' => SendEnrolmentMessage::courseTitle($this->enrolment),
            'url' => SendEnrolmentMessage::learnerUrl($this->enrolment),
            'position' => $this->enrolment->waitlistPosition(),
        ]);
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        if ($this->enrolment->status !== EnrolmentStatus::Enrolled || ! in_array($this->kind, [SendEnrolmentMessage::ENROLLED, SendEnrolmentMessage::PROMOTED, SendEnrolmentMessage::AGAIN], true)) {
            return [];
        }

        $ics = CohortCalendar::render($this->enrolment->cohort, $this->enrolment->locale);

        return $ics === null ? [] : [Attachment::fromData(fn (): string => $ics, 'course.ics')->withMime('text/calendar')];
    }
}
