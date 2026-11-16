<?php

namespace App\Domain\Volunteers\Notifications;

use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Notifications\StaffNotice;
use App\Domain\Volunteers\Models\Volunteer;
use App\Filament\Resources\Volunteers\VolunteerResource;

/**
 * Someone offered to volunteer in an office the coordinator handles
 * (ADR-0048). Only the office: the person's details stay in the admin.
 * Unread notices for one office are combined.
 */
final class NewVolunteerNotice extends StaffNotice
{
    /**
     * @param  array<string, string>  $offices  the office's name per language, empty without an office
     */
    public function __construct(
        public readonly string $volunteerId,
        public readonly ?string $officeId,
        public readonly array $offices,
    ) {}

    public static function for(Volunteer $volunteer): self
    {
        $office = $volunteer->office;

        return new self(
            $volunteer->id,
            $volunteer->office_id,
            $office === null ? [] : self::perLanguage(fn (string $locale): string => $office->name($locale)),
        );
    }

    public function kind(): NoticeKind
    {
        return NoticeKind::Volunteers;
    }

    public function group(): string
    {
        return 'volunteers:'.($this->officeId ?? 'none');
    }

    protected function title(string $locale): string
    {
        return __('volunteers.notice.title', [], $locale);
    }

    protected function lines(string $locale): array
    {
        return [$this->offices === []
            ? __('volunteers.notice.no_office', [], $locale)
            : __('volunteers.notice.office', ['office' => self::pick($this->offices, $locale)], $locale)];
    }

    protected function url(): string
    {
        return VolunteerResource::getUrl('view', ['record' => $this->volunteerId], panel: 'admin');
    }

    protected function actionLabel(string $locale): string
    {
        return __('volunteers.notice.open', [], $locale);
    }

    protected function icon(): string
    {
        return 'heroicon-o-hand-raised';
    }
}
