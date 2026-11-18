<?php

namespace App\Console\Commands;

use App\Domain\Health\Support\LaunchCheck;
use Illuminate\Console\Command;

/**
 * What stands between the site and its launch (ADR-0042): each item ready, missing (with who supplies it) or
 * deferred by decision. `--strict` fails while anything is missing, for use just before switching to production.
 */
final class LaunchCheckCommand extends Command
{
    protected $signature = 'portal:launch-check {--strict : Fail while anything is missing}';

    protected $description = 'List what is ready, missing or deferred for the launch';

    public function handle(): int
    {
        $items = LaunchCheck::run();
        $this->table(['Item', 'Status', 'Detail', 'Who'], array_map(fn (array $i): array => [$i['key'], strtoupper($i['status']), $i['detail'], $i['owner']], $items));

        $missing = count(array_filter($items, fn (array $i): bool => $i['status'] === LaunchCheck::MISSING));
        $this->line(sprintf('%d ready, %d missing, %d deferred.', count(array_filter($items, fn (array $i): bool => $i['status'] === LaunchCheck::READY)), $missing,
            count(array_filter($items, fn (array $i): bool => $i['status'] === LaunchCheck::DEFERRED))));

        return $this->option('strict') && $missing > 0 ? self::FAILURE : self::SUCCESS;
    }
}
