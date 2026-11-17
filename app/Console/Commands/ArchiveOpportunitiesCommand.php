<?php

namespace App\Console\Commands;

use App\Domain\Opportunities\Models\Opportunity;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * The automatic archive (ADR-0032): every five minutes, each opportunity whose deadline has passed is marked as
 * archived. The pages already read the state from the dates; the save purges the edge cache (ADR-0039 of the fork), so no cached
 * page goes on showing a closed tender as open.
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
                    $opportunity->archived_at = $now;
                    $opportunity->save();
                    $count++;
                }
            });

        $this->info("Archived {$count}.");

        return self::SUCCESS;
    }
}
