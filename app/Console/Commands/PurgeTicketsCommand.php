<?php

namespace App\Console\Commands;

use App\Domain\Support\Actions\StoreAttachments;
use App\Domain\Support\Enums\TicketStatus;
use App\Domain\Support\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Deletes support tickets closed (or resolved and never rated) longer ago than the retention period, with their
 * messages and attachments (ADR-0017). Open tickets are never deleted. Runs for every site.
 */
final class PurgeTicketsCommand extends Command
{
    protected $signature = 'support:purge-tickets';

    protected $description = 'Delete closed support tickets past the retention period';

    public function handle(): int
    {
        $before = CarbonImmutable::now()->subDays(max(30, (int) config('portal.support.retention_days')));
        $deleted = 0;

        Ticket::query()
            ->withoutGlobalScopes()
            ->where(fn ($q) => $q
                ->where(fn ($c) => $c->where('status', TicketStatus::Closed->value)->where('closed_at', '<', $before))
                ->orWhere(fn ($r) => $r->where('status', TicketStatus::Resolved->value)->where('resolved_at', '<', $before)))
            ->chunkById(100, function ($tickets) use (&$deleted): void {
                foreach ($tickets as $ticket) {
                    /** @var Ticket $ticket */
                    StoreAttachments::deleteFolder($ticket->id);
                    $ticket->messages()->delete();
                    $ticket->delete();
                    $deleted++;
                }
            });

        $this->components->info("Support tickets deleted: {$deleted}");

        return self::SUCCESS;
    }
}
