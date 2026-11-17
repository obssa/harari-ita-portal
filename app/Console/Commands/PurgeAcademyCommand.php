<?php

namespace App\Console\Commands;

use App\Domain\Academy\Models\Enrolment;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies the Academy's retention rule (ADR-0029, PLAN.md §11): `portal.academy.retention_days` after a cohort ends,
 * enrolments without a certificate are deleted with their attendance, and those with one lose the learner's name,
 * phone, email and link. The certificate keeps the holder's name so it can still be printed by staff and verified.
 * Runs daily.
 */
final class PurgeAcademyCommand extends Command
{
    protected $signature = 'academy:purge';

    protected $description = 'Remove learners\' details past the retention period';

    public function handle(): int
    {
        $before = CarbonImmutable::today()->subDays(max(1, (int) config('portal.academy.retention_days')));
        $ended = fn (Builder $cohort) => $cohort->whereDate('ends_on', '<', $before->toDateString());

        $deleted = Enrolment::query()->whereHas('cohort', $ended)->whereDoesntHave('certificates')->delete();

        $kept = 0;
        Enrolment::query()->whereHas('cohort', $ended)->whereHas('certificates')->whereNull('erased_at')
            ->chunkById(200, function ($enrolments) use (&$kept): void {
                /** @var Enrolment $enrolment */
                foreach ($enrolments as $enrolment) {
                    $enrolment->forceFill([
                        'name' => null, 'phone' => null, 'email' => null, 'phone_hash' => null, 'email_hash' => null,
                        'token' => null, 'token_hash' => Enrolment::hash(bin2hex(random_bytes(20))), 'erased_at' => now(),
                    ])->saveQuietly();
                    $kept++;
                }
            });

        $this->components->info("Enrolments deleted: {$deleted}; certified enrolments anonymised: {$kept}");

        return self::SUCCESS;
    }
}
