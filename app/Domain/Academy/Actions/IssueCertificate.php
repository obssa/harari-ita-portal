<?php

namespace App\Domain\Academy\Actions;

use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\Certificate;
use App\Domain\Academy\Models\Enrolment;
use App\Domain\Academy\Support\CohortScope;
use App\Models\User;
use App\Support\Initials;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Issues one learner's certificate (ADR-0030), once the cohort has started, and tells them. One certificate stands
 * per enrolment; after a revocation a new one can be issued. The holder's initials are kept for verification and the
 * full name for printing.
 */
final class IssueCertificate
{
    public function __construct(private readonly SendEnrolmentMessage $messages) {}

    public function handle(Enrolment $enrolment, User $actor, bool $notify = true): Certificate
    {
        $cohort = $enrolment->loadMissing('cohort')->cohort;
        if (! $actor->can('certificates.issue') || ! CohortScope::covers($actor, $cohort)) {
            throw new AuthorizationException;
        }
        if ($enrolment->status !== EnrolmentStatus::Enrolled || $enrolment->erased_at !== null || $enrolment->name === null) {
            throw ValidationException::withMessages(['enrolment' => __('academy.validation.not_enrolled')]);
        }
        if ($cohort->starts_on > CarbonImmutable::today()) {
            throw ValidationException::withMessages(['enrolment' => __('academy.validation.not_started')]);
        }
        if ($enrolment->certificate()->exists()) {
            throw ValidationException::withMessages(['enrolment' => __('academy.validation.already_certified')]);
        }

        $certificate = new Certificate([
            'enrolment_id' => $enrolment->id,
            'course_id' => $cohort->course_id,
            'course_cohort_id' => $cohort->id,
            'code' => Certificate::newCode(),
            'holder_name' => $enrolment->name,
            'holder_initials' => self::initials($enrolment->name),
            'issued_at' => CarbonImmutable::now()->startOfSecond(),
            'issued_by' => $actor->id,
        ]);
        $certificate->signature = $certificate->computeSignature();
        $certificate->save();

        if ($notify) {
            $this->messages->handle($enrolment, SendEnrolmentMessage::CERTIFICATE);
        }

        return $certificate;
    }

    /** "Sample Learner" → "S. L." */
    public static function initials(string $name): string
    {
        $letters = mb_str_split(Initials::of($name));

        return $letters === [] ? '—' : implode(' ', array_map(fn (string $letter): string => $letter.'.', $letters));
    }
}
