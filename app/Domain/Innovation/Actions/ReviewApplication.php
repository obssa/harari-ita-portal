<?php

namespace App\Domain\Innovation\Actions;

use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Mail\ApplicationMail;
use App\Domain\Innovation\Models\Application;
use App\Models\User;
use Carbon\CarbonImmutable;
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
        if ($stage === $application->stage) {
            return $application;
        }

        $application->stage = $stage;
        if ($stage->isDecision()) {
            $application->decision_note = mb_substr($note, 0, 2000);
            $application->decided_at = CarbonImmutable::now();
        }
        $application->save();

        if ($stage->isDecision() && $application->applicant_email !== null) {
            Mail::to($application->applicant_email)->locale($application->locale)->queue(new ApplicationMail($application, 'decided'));
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

        $application->scores = $clean;
        $application->score = number_format(array_sum($clean) / count($clean), 2, '.', '');
        $application->scored_by = $actor->id;
        if ($application->stage === ApplicationStage::Received) {
            $application->stage = ApplicationStage::Screening;
        }
        $application->save();

        return $application;
    }

    public function withdraw(Application $application): Application
    {
        if (! $application->stage->isOpen()) {
            throw ValidationException::withMessages(['form' => __('innovation.validation.not_open')]);
        }
        $application->stage = ApplicationStage::Withdrawn;
        $application->save();

        return $application;
    }
}
