<?php

namespace App\Filament\Widgets;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Dashboard\Contracts\DashboardWidget;
use App\Domain\Status\Models\Incident;
use App\Domain\Support\Enums\SlaState;
use App\Domain\Support\Enums\TicketStatus;
use App\Domain\Support\Models\Ticket;
use App\Filament\Support\CurrentUser;
use App\Filament\Widgets\Concerns\IsDashboardWidget;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

/**
 * The service desk at a glance (PLAN.md §6; ADR-0017, ADR-0018): open tickets of one's directorates by SLA state,
 * and the incidents active on the status page.
 */
class ServiceDesk extends Widget implements DashboardWidget
{
    use IsDashboardWidget;

    protected string $view = 'filament.widgets.service-desk';

    public static function key(): string
    {
        return 'service_desk';
    }

    public static function title(): string
    {
        return __('dashboard.widget.service_desk');
    }

    public static function permission(): ?string
    {
        return 'tickets.view';
    }

    public static function defaultSize(): int
    {
        return 6;
    }

    /**
     * @return array<string, int>
     */
    public function sla(): array
    {
        $counts = [SlaState::OnTime->value => 0, SlaState::DueSoon->value => 0, SlaState::Breached->value => 0];
        OfficeScope::apply(Ticket::query(), CurrentUser::get(), 'tickets.view')
            ->whereIn('status', [TicketStatus::New->value, TicketStatus::InProgress->value, TicketStatus::Waiting->value])
            ->select(['id', 'status', 'resolve_due_at', 'resolved_at', 'closed_at'])
            ->limit(2000)
            ->get()
            ->each(function (Ticket $ticket) use (&$counts): void {
                $counts[$ticket->slaState()->value] = ($counts[$ticket->slaState()->value] ?? 0) + 1;
            });

        return $counts;
    }

    /**
     * @return Collection<int, Incident>
     */
    public function incidents(): Collection
    {
        return CurrentUser::get()->can('status.view')
            ? Incident::query()->active()->orderByDesc('started_at')->limit(5)->get()
            : new Collection;
    }
}
