<?php

namespace App\Domain\Innovation\Actions;

use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Mail\ApplicationMail;
use App\Domain\Innovation\Models\Application;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Moving an application through its stages and scoring it (ADR-0021). Reviewers (`applications.review`) move it
 * between received, screening and interview and score it; deciding (accepted, waitlisted, declined) needs
 * `applications.decide` and a note the applicant reads. Applicants are told by email of each decision.
 */
final class ReviewApplication
{
    public function move(Application $application, ApplicationStage $stage, ?string $note, User $actor): Application
    {
        if ($stage === ApplicationStage::Withdrawn) {
            throw ValidationException::withMessages(['stage' => __('innovation.validation.withdrawn_by_applicant')]);
        }
        if ($stage->isDecision() && ! $actor->can('applications.decide')) {
            throw ValidationException::withMessages(['stage' => __('innovation.validation.decide')]);
        }
        $note = trim((string) $note);
        if ($stage->isDecision() && $note === '') {
            throw ValidationException::withMessages(['note' => __('innovation.validation.decision_note')]);
        }
        // Decided on the locked row (ADR-0054): a stale screen cannot overwrite an applicant's withdrawal or reopen a
        // decision.
        $moved = DB::transaction(function () use ($application, $stage, $note): bool {
            $locked = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->stage === $stage) {
                return false;
            }
            if (! $locked->stage->canMoveTo($stage)) {
                throw ValidationException::withMessages(['stage' => __('innovation.validation.stage_move')]);
            }
            $locked->stage = $stage;
            if ($stage->isDecision()) {
                $locked->decision_note = mb_substr($note, 0, 2000);
                $locked->decided_at = CarbonImmutable::now();
            }
            $locked->save();

            return true;
        });
        $application->refresh();
        if (! $moved) {
            return $application;
        }

        if ($stage->isDecision() && $application->applicant_email !== null) {
            Mail::to($application->applicant_email)->locale($application->locale)->queue(new ApplicationMail($application, 'decided'));
        } else {
            $this->tellStage($application);
        }

        return $application;
    }

    /**
     * @param  array<string, mixed>  $scores  criterion => 1..5
     */
    public function score(Application $application, array $scores, User $actor): Application
    {
        $clean = [];
        foreach (Application::CRITERIA as $criterion) {
            $value = (int) ($scores[$criterion] ?? 0);
            if ($value < 1 || $value > 5) {
                throw ValidationException::withMessages(["scores.{$criterion}" => __('innovation.validation.score')]);
            }
            $clean[$criterion] = $value;
        }

        // Only an open application is scored; a decided or withdrawn one keeps the scores it was decided on.
        $moved = DB::transaction(function () use ($application, $clean, $actor): bool {
            $locked = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
            if (! $locked->stage->isOpen()) {
                throw ValidationException::withMessages(['scores' => __('innovation.validation.not_open')]);
            }
            $locked->scores = $clean;
            $locked->score = number_format(array_sum($clean) / count($clean), 2, '.', '');
            $locked->scored_by = $actor->id;
            $moved = $locked->stage === ApplicationStage::Received;
            if ($moved) {
                $locked->stage = ApplicationStage::Screening;
            }
            $locked->save();

            return $moved;
        });
        $application->refresh();
        if ($moved) {
            $this->tellStage($application);
        }

        return $application;
    }

    /** ADR-0053: screening and interview are worth an email; the decision has its own. */
    private function tellStage(Application $application): void
    {
        if (in_array($application->stage, [ApplicationStage::Screening, ApplicationStage::Interview], true) && $application->applicant_email !== null) {
            Mail::to($application->applicant_email)->locale($application->locale)->queue(new ApplicationMail($application, 'stage'));
        }
    }

    public function withdraw(Application $application): Application
    {
        DB::transaction(function () use ($application): void {
            $locked = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
            if (! $locked->stage->isOpen()) {
                throw ValidationException::withMessages(['form' => __('innovation.validation.not_open')]);
            }
            $locked->stage = ApplicationStage::Withdrawn;
            // The retention period counts from the withdrawal (ADR-0054).
            $locked->decided_at = CarbonImmutable::now();
            $locked->save();
        });

        return $application->refresh();
    }
}
