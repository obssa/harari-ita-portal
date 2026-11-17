<?php

namespace App\Domain\Academy\Notifications;

use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Notifications\StaffNotice;
use App\Filament\Resources\CourseCohorts\CourseCohortResource;

/**
 * A learner enrolled in a cohort (ADR-0029). The counts are taken when the enrolment arrives; an unread notice for
 * the same cohort is replaced, so staff see one notice per cohort with the latest numbers. No names.
 */
final class EnrolmentNotice extends StaffNotice
{
    /**
     * @param  array<string, string>  $titles  the course and cohort per language
     */
    public function __construct(
        public readonly int $cohortId,
        public readonly array $titles,
        public readonly int $enrolled,
        public readonly ?int $placesLeft,
        public readonly int $waiting,
    ) {}

    public static function for(CourseCohort $cohort): self
    {
        return new self(
            $cohort->id,
            self::perLanguage(fn (string $locale): string => $cohort->label($locale)),
            $cohort->enrolledCount(),
            $cohort->placesLeft(),
            $cohort->enrolments()->where('status', EnrolmentStatus::Waitlisted->value)->count(),
        );
    }

    public function kind(): NoticeKind
    {
        return NoticeKind::Registrations;
    }

    public function group(): string
    {
        return "enrolments:{$this->cohortId}";
    }

    protected function title(string $locale): string
    {
        $cohort = self::pick($this->titles, $locale);

        return $this->placesLeft === 0
            ? __('academy.notice.full_title', ['cohort' => $cohort], $locale)
            : __('academy.notice.title', ['cohort' => $cohort], $locale);
    }

    protected function lines(string $locale): array
    {
        $lines = [$this->placesLeft === null
            ? __('academy.notice.count_open', ['count' => $this->enrolled], $locale)
            : __('academy.notice.count', ['count' => $this->enrolled, 'left' => $this->placesLeft], $locale)];
        if ($this->waiting > 0) {
            $lines[] = __('academy.notice.waiting', ['count' => $this->waiting], $locale);
        }

        return $lines;
    }

    protected function url(): string
    {
        return CourseCohortResource::getUrl('edit', ['record' => $this->cohortId], panel: 'admin');
    }

    protected function actionLabel(string $locale): string
    {
        return __('academy.notice.open', [], $locale);
    }

    protected function icon(): string
    {
        return 'heroicon-o-academic-cap';
    }
}
