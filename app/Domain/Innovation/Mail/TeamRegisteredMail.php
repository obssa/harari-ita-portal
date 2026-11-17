<?php

namespace App\Domain\Innovation\Mail;

use App\Domain\Innovation\Models\ChallengeTeam;
use App\Services\Calendar\EthiopianCalendar;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The receipt to a team's contact (ADR-0022): the team reference, which with the contact's email or phone opens the
 * team page where the submission is sent.
 */
final class TeamRegisteredMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public ChallengeTeam $team) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('innovation.mail.team.subject', [
            'challenge' => $this->team->challenge->title($this->team->locale),
            'reference' => $this->team->reference,
        ]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.innovation.team', with: [
            'name' => $this->team->contact_name,
            'team' => $this->team->name,
            'challenge' => $this->team->challenge->title($this->team->locale),
            'closes' => app(EthiopianCalendar::class)->format($this->team->challenge->submission_closes_at, $this->team->locale, withTime: true),
            'reference' => $this->team->reference,
            'url' => route('teams.lookup', ['locale' => $this->team->locale, 'reference' => $this->team->reference]),
        ]);
    }
}
