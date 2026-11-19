<?php

namespace App\Console\Commands;

use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Enums\ListingStatus;
use App\Domain\Innovation\Models\Application;
use App\Domain\Innovation\Models\ChallengeTeam;
use App\Domain\Innovation\Models\Startup;
use App\Domain\People\Models\PersonNote;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * The innovation retention period (ADR-0021, ADR-0020), for every site: applications are deleted once they were
 * decided or withdrawn longer ago than the period; listing requests that were declined are deleted, and archived
 * listings lose the founders' contact; teams lose their contact once the challenge is that old (ADR-0022). Open
 * applications and listed startups are never touched.
 */
final class PurgeInnovationCommand extends Command
{
    protected $signature = 'innovation:purge';

    protected $description = 'Delete decided applications and declined listing requests past the retention period';

    public function handle(): int
    {
        $before = CarbonImmutable::now()->subDays(max(30, (int) config('portal.innovation.retention_days', 730)));

        $expired = Application::query()->withoutGlobalScopes()
            ->whereIn('stage', [ApplicationStage::Accepted->value, ApplicationStage::Waitlisted->value, ApplicationStage::Declined->value, ApplicationStage::Withdrawn->value])
            ->where('updated_at', '<', $before);
        // ADR-0053: the history (and the messages in it) goes with the application.
        $expired->clone()->pluck('id')->chunk(500)->each(fn ($ids) => PersonNote::query()
            ->where('notable_type', (new Application)->getMorphClass())->whereIn('notable_id', $ids->all())->delete());
        $applications = $expired->delete();

        // ADR-0022: once a challenge's results are older than the period, its teams lose the contact and members;
        // the ranked names and submissions stay as the record of the challenge.
        $clearing = ChallengeTeam::query()->withoutGlobalScopes()->whereNull('erased_at')
            ->whereHas('challenge', fn ($q) => $q->withoutGlobalScopes()->where(fn ($q) => $q
                ->where('results_published_at', '<', $before)
                ->orWhere(fn ($q) => $q->whereNull('results_published_at')->where('submission_closes_at', '<', $before))));
        $clearing->clone()->pluck('id')->chunk(500)->each(fn ($ids) => PersonNote::query()
            ->where('notable_type', (new ChallengeTeam)->getMorphClass())->whereIn('notable_id', $ids->all())->delete());
        $teams = $clearing->update(['contact_name' => null, 'contact_email' => null, 'contact_phone' => null, 'email_hash' => null, 'phone_hash' => null, 'members' => null, 'erased_at' => now()]);

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

        $this->components->info("Applications deleted: {$applications}; teams cleared: {$teams}; declined listings deleted: {$declined}; archived listings cleared: {$archived}");

        return self::SUCCESS;
    }
}
