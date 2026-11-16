<?php

namespace App\Console\Commands;

use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Enums\ListingStatus;
use App\Domain\Innovation\Models\Application;
use App\Domain\Innovation\Models\Startup;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * The innovation retention period (ADR-0021, ADR-0020), for every site: applications are deleted once they were
 * decided or withdrawn longer ago than the period; listing requests that were declined are deleted, and archived
 * listings lose the founders' contact. Open applications and listed startups are never touched.
 */
final class PurgeInnovationCommand extends Command
{
    protected $signature = 'innovation:purge';

    protected $description = 'Delete decided applications and declined listing requests past the retention period';

    public function handle(): int
    {
        $before = CarbonImmutable::now()->subDays(max(30, (int) config('portal.innovation.retention_days', 730)));

        $applications = Application::query()->withoutGlobalScopes()
            ->whereIn('stage', [ApplicationStage::Accepted->value, ApplicationStage::Waitlisted->value, ApplicationStage::Declined->value, ApplicationStage::Withdrawn->value])
            ->where('updated_at', '<', $before)
            ->delete();

        $declined = 0;
        Startup::query()->withoutGlobalScopes()->withTrashed()
            ->where('listing_status', ListingStatus::Declined->value)
            ->where('updated_at', '<', $before)
            ->each(function (Startup $startup) use (&$declined): void {
                $startup->forceDelete();
                $declined++;
            });

        $archived = Startup::query()->withoutGlobalScopes()->withTrashed()
            ->where('listing_status', ListingStatus::Archived->value)
            ->where('updated_at', '<', $before)
            ->whereNotNull('email_hash')
            ->update(['founder_name' => null, 'founder_email' => null, 'founder_phone' => null, 'email_hash' => null, 'phone_hash' => null]);

        $this->components->info("Applications deleted: {$applications}; declined listings deleted: {$declined}; archived listings cleared: {$archived}");

        return self::SUCCESS;
    }
}
