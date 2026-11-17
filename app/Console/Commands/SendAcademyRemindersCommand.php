<?php

namespace App\Console\Commands;

use App\Domain\Academy\Actions\SendEnrolmentMessage;
use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\Enrolment;
use App\Services\Calendar\EthiopianCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reminds every enrolled learner, once, the day before their cohort starts (ADR-0029; runs every hour, so a learner
 * who enrols on the eve still gets it).
 */
final class SendAcademyRemindersCommand extends Command
{
    protected $signature = 'academy:send-reminders';

    protected $description = 'Remind learners of cohorts starting tomorrow';

    public function handle(SendEnrolmentMessage $messages): int
    {
        // Days are counted in Ethiopia: a cohort's first day is a date there (ADR-0029).
        $today = CarbonImmutable::today(EthiopianCalendar::timezone());
        $count = 0;

        Enrolment::query()
            ->where('status', EnrolmentStatus::Enrolled->value)
            ->whereNull('reminded_at')
            ->whereNull('erased_at')
            ->whereHas('cohort', fn (Builder $cohort) => $cohort->whereNull('cancelled_at')
                ->whereDate('starts_on', '>', $today->toDateString())
                ->whereDate('starts_on', '<=', $today->addDay()->toDateString()))
            ->with('cohort.course.translations')
            ->chunkById(200, function ($enrolments) use ($messages, &$count): void {
                /** @var Enrolment $enrolment */
                foreach ($enrolments as $enrolment) {
                    $enrolment->reminded_at = CarbonImmutable::now();
                    $enrolment->save();
                    $messages->handle($enrolment, SendEnrolmentMessage::REMINDER);
                    $count++;
                }
            });

        $this->components->info("Reminders sent: {$count}");

        return self::SUCCESS;
    }
}
