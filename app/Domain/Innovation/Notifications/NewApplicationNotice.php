<?php

namespace App\Domain\Innovation\Notifications;

use App\Domain\Innovation\Models\Application;
use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Notifications\StaffNotice;
use App\Filament\Resources\Applications\ApplicationResource;

/**
 * A new application to a programme (ADR-0021). It names the programme and the reference; the applicant's details
 * stay in the admin.
 */
final class NewApplicationNotice extends StaffNotice
{
    /**
     * @param  array<string, string>  $programmes  the programme's title per language
     */
    public function __construct(
        public readonly string $applicationId,
        public readonly string $reference,
        public readonly array $programmes,
    ) {}

    public static function for(Application $application): self
    {
        $programme = $application->programme;

        return new self($application->id, $application->reference, self::perLanguage(fn (string $locale): string => $programme->title($locale)));
    }

    public function kind(): NoticeKind
    {
        return NoticeKind::Workflow;
    }

    protected function title(string $locale): string
    {
        return __('innovation.notice.application_title', ['programme' => self::pick($this->programmes, $locale)], $locale);
    }

    protected function lines(string $locale): array
    {
        return [__('notifications.ticket.reference', ['reference' => $this->reference], $locale)];
    }

    protected function url(): string
    {
        return ApplicationResource::getUrl('view', ['record' => $this->applicationId], panel: 'admin');
    }

    protected function actionLabel(string $locale): string
    {
        return __('innovation.notice.open_application', [], $locale);
    }

    protected function icon(): string
    {
        return 'heroicon-o-clipboard-document-list';
    }
}
