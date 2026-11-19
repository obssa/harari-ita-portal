<?php

namespace App\Domain\Innovation\Notifications;

use App\Domain\Innovation\Models\ChallengeTeam;
use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Notifications\StaffNotice;
use App\Filament\Resources\ChallengeTeams\ChallengeTeamResource;

/**
 * A team registered for a challenge, or sent its work (ADR-0053). It names the challenge and the team reference;
 * the contact's details stay in the admin.
 */
final class TeamNotice extends StaffNotice
{
    public const REGISTERED = 'registered';

    public const SUBMITTED = 'submitted';

    /**
     * @param  array<string, string>  $challenges  the challenge's title per language
     */
    public function __construct(
        public readonly string $teamId,
        public readonly string $reference,
        public readonly string $event,
        public readonly array $challenges,
    ) {}

    public static function for(ChallengeTeam $team, string $event): self
    {
        $challenge = $team->challenge;

        return new self($team->id, $team->reference, $event, self::perLanguage(fn (string $locale): string => $challenge->title($locale)));
    }

    public function kind(): NoticeKind
    {
        return NoticeKind::Workflow;
    }

    protected function title(string $locale): string
    {
        return __("innovation.notice.team_{$this->event}", ['challenge' => self::pick($this->challenges, $locale)], $locale);
    }

    protected function lines(string $locale): array
    {
        return [__('notifications.ticket.reference', ['reference' => $this->reference], $locale)];
    }

    protected function url(): string
    {
        return ChallengeTeamResource::getUrl('view', ['record' => $this->teamId], panel: 'admin');
    }

    protected function actionLabel(string $locale): string
    {
        return __('innovation.notice.open_team', [], $locale);
    }

    protected function icon(): string
    {
        return 'heroicon-o-user-group';
    }
}
