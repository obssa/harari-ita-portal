<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Support\Enums\TicketStatus;
use App\Domain\Support\Models\Ticket;
use App\Filament\Resources\Tickets\TicketResource;
use App\Filament\Support\CurrentUser;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * The ticket board (PLAN.md §6 Kanban; ADR-0017): a column per status, breached tickets first, then the nearest
 * target. Cards open the ticket; moving a ticket is done there, where the reason can be written.
 */
class TicketBoard extends Page
{
    protected static string $resource = TicketResource::class;

    protected string $view = 'filament.support.board';

    /** Resolved tickets stay on the board for this many days. */
    public const RESOLVED_DAYS = 7;

    public function getTitle(): string
    {
        return __('support.admin.board');
    }

    public static function canAccess(array $parameters = []): bool
    {
        return CurrentUser::get()->can('tickets.view');
    }

    /**
     * @return array<string, Collection<int, Ticket>>
     */
    public function columns(): array
    {
        $tickets = OfficeScope::apply(Ticket::query(), CurrentUser::get(), 'tickets.view')
            ->with(['assignee'])
            ->where(fn ($q) => $q->whereIn('status', [TicketStatus::New->value, TicketStatus::InProgress->value, TicketStatus::Waiting->value])
                ->orWhere(fn ($r) => $r->where('status', TicketStatus::Resolved->value)->where('resolved_at', '>=', CarbonImmutable::now()->subDays(self::RESOLVED_DAYS))))
            ->orderBy('resolve_due_at')
            ->limit(400)
            ->get();

        $columns = [];
        foreach ([TicketStatus::New, TicketStatus::InProgress, TicketStatus::Waiting, TicketStatus::Resolved] as $status) {
            $columns[$status->value] = $tickets->where('status', $status)->values();
        }

        return $columns;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list')
                ->label(__('support.admin.list'))
                ->icon(Heroicon::OutlinedListBullet)
                ->color('gray')
                ->url(TicketResource::getUrl('index')),
        ];
    }
}
