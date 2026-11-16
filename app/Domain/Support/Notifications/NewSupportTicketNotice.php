<?php

namespace App\Domain\Support\Notifications;

use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Notifications\StaffNotice;
use App\Domain\Support\Models\Ticket;
use App\Filament\Resources\Tickets\TicketResource;
use App\Services\Calendar\EthiopianCalendar;
use Carbon\CarbonImmutable;

/**
 * A support request arrived, or its requester wrote back (ADR-0017). It names the reference, the category, the
 * priority and the directorate; the requester's details and words stay in the admin.
 */
final class NewSupportTicketNotice extends StaffNotice
{
    /**
     * @param  array<string, string>  $offices  the directorate's name per language, empty without one
     */
    public function __construct(
        public readonly string $ticketId,
        public readonly string $reference,
        public readonly string $category,
        public readonly string $priority,
        public readonly array $offices,
        public readonly ?string $dueAt,
        public readonly bool $reply = false,
    ) {}

    public static function for(Ticket $ticket, bool $reply = false): self
    {
        $office = $ticket->office;

        return new self(
            $ticket->id,
            $ticket->reference,
            $ticket->category->value,
            $ticket->priority->value,
            $office === null ? [] : self::perLanguage(fn (string $locale): string => $office->name($locale)),
            $ticket->response_due_at?->toIso8601String(),
            $reply,
        );
    }

    public function kind(): NoticeKind
    {
        return NoticeKind::Tickets;
    }

    protected function title(string $locale): string
    {
        return $this->reply
            ? __('support.notice.reply_title', ['reference' => $this->reference], $locale)
            : __('support.notice.title', ['category' => __("support.category.{$this->category}", [], $locale)], $locale);
    }

    protected function lines(string $locale): array
    {
        $lines = [
            __('notifications.ticket.reference', ['reference' => $this->reference], $locale),
            __('support.notice.priority', ['priority' => __("support.priority.{$this->priority}", [], $locale)], $locale),
        ];
        if ($this->offices !== []) {
            $lines[] = __('notifications.ticket.office', ['office' => self::pick($this->offices, $locale)], $locale);
        }
        if ($this->dueAt !== null && ! $this->reply) {
            $lines[] = __('support.notice.due', [
                'date' => app(EthiopianCalendar::class)->format(CarbonImmutable::parse($this->dueAt), $locale),
            ], $locale);
        }

        return $lines;
    }

    protected function url(): string
    {
        return TicketResource::getUrl('view', ['record' => $this->ticketId], panel: 'admin');
    }

    protected function actionLabel(string $locale): string
    {
        return __('notifications.ticket.open', [], $locale);
    }

    protected function icon(): string
    {
        return 'heroicon-o-lifebuoy';
    }
}
