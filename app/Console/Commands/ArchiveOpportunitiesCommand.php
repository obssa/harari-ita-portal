<?php

namespace App\Console\Commands;

use App\Domain\Opportunities\Models\Opportunity;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The automatic archive (ADR-0032): every five minutes, each opportunity whose deadline has passed is marked as
 * archived. The pages already read the state from the dates; the save purges the edge cache (ADR-0039 of the fork), so no cached
 * page goes on showing a closed tender as open. Each one is checked again on the locked row (ADR-0054), so an
 * extension published while the list was read is not archived.
 */
final class ArchiveOpportunitiesCommand extends Command
{
    protected $signature = 'opportunities:archive';

    protected $description = 'Archive the opportunities whose deadline has passed';

    public function handle(): int
    {
        $now = CarbonImmutable::now();
        $count = 0;
        Opportunity::query()->whereNull('archived_at')->where('closes_at', '<=', $now)
            ->chunkById(100, function ($opportunities) use ($now, &$count): void {
                /** @var Opportunity $opportunity */
                foreach ($opportunities as $opportunity) {
                    $archived = DB::transaction(function () use ($opportunity, $now): bool {
                        $locked = Opportunity::query()->whereKey($opportunity->getKey())->lockForUpdate()->first();
                        if (! $locked instanceof Opportunity || $locked->archived_at !== null || $locked->closes_at > $now) {
                            return false;
                        }
                        $locked->archived_at = $now;
                        $locked->save();

                        return true;
                    });
                    $count += (int) $archived;
                }
            });

        $this->info("Archived {$count}.");

        return self::SUCCESS;
    }
}
