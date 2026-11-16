<?php

namespace App\Console\Commands;

use App\Domain\Engagement\Actions\Subscribe;
use App\Domain\People\Models\PersonNote;
use App\Domain\Volunteers\Enums\VolunteerStatus;
use App\Domain\Volunteers\Models\Volunteer;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Sign-ups nobody acted on: volunteer sign-ups nobody followed up within a
 * year are deleted with their history, and newsletter sign-ups never
 * confirmed after a week.
 */
final class PurgeSignUpsCommand extends Command
{
    public const UNFOLLOWED_VOLUNTEER_DAYS = 365;

    protected $signature = 'portal:purge-sign-ups';

    protected $description = 'Delete volunteer sign-ups nobody followed up and newsletter sign-ups never confirmed';

    public function handle(): int
    {
        $volunteers = $this->purgeVolunteers(CarbonImmutable::now());
        $subscribers = Subscribe::purge();

        $this->components->info("Old volunteer sign-ups deleted: {$volunteers}; unconfirmed subscribers deleted: {$subscribers}");

        return self::SUCCESS;
    }

    private function purgeVolunteers(CarbonImmutable $now): int
    {
        $ids = Volunteer::query()->withoutGlobalScopes()
            ->where('status', VolunteerStatus::New->value)
            ->where('created_at', '<', $now->subDays(self::UNFOLLOWED_VOLUNTEER_DAYS))
            ->pluck('id')->all();
        foreach (array_chunk($ids, 500) as $chunk) {
            PersonNote::query()->where('notable_type', Volunteer::class)->whereIn('notable_id', $chunk)->delete();
            Volunteer::query()->withoutGlobalScopes()->whereKey($chunk)->delete();
        }

        return count($ids);
    }
}
