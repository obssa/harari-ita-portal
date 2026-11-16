<?php

namespace App\Domain\Innovation\Notifications;

use App\Domain\Innovation\Models\Startup;
use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Notifications\StaffNotice;
use App\Filament\Resources\Startups\StartupResource;

/**
 * A startup asked to be listed (ADR-0020). It names the startup and the reference; the founders' contact stays in
 * the admin.
 */
final class NewStartupNotice extends StaffNotice
{
    public function __construct(
        public readonly string $startupId,
        public readonly string $reference,
        public readonly string $name,
    ) {}

    public static function for(Startup $startup): self
    {
        return new self($startup->id, $startup->reference, $startup->name());
    }

    public function kind(): NoticeKind
    {
        return NoticeKind::Workflow;
    }

    protected function title(string $locale): string
    {
        return __('innovation.notice.title', ['name' => $this->name], $locale);
    }

    protected function lines(string $locale): array
    {
        return [__('notifications.ticket.reference', ['reference' => $this->reference], $locale)];
    }

    protected function url(): string
    {
        return StartupResource::getUrl('edit', ['record' => $this->startupId], panel: 'admin');
    }

    protected function actionLabel(string $locale): string
    {
        return __('innovation.notice.open', [], $locale);
    }

    protected function icon(): string
    {
        return 'heroicon-o-rocket-launch';
    }
}
