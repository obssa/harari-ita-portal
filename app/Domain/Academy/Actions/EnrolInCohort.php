<?php

namespace App\Domain\Academy\Actions;

use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\Enrolment;
use App\Domain\Academy\Notifications\EnrolmentNotice;
use App\Domain\Notifications\Support\Recipients;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Takes a place in a cohort, or a place on its waiting list when it is full (ADR-0029). The same phone number or
 * email enrols once per cohort; trying again sends the existing link again, to the details already given.
 */
final class EnrolInCohort
{
    public function __construct(private readonly SendEnrolmentMessage $messages) {}

    /**
     * @param  array<string, mixed>  $data  name, phone, email, consent
     */
    public function handle(CourseCohort $cohort, array $data, string $locale): EnrolmentResult
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', (string) ($data['name'] ?? '')));
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $phoneInput = trim((string) ($data['phone'] ?? ''));
        $phone = PhoneNumber::normalise($phoneInput);

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['name'] = __('academy.validation.name');
        }
        if ($phoneInput !== '' && $phone === null) {
            $errors['phone'] = __('academy.validation.phone');
        }
        if ($email !== '' && (mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            $errors['email'] = __('academy.validation.email');
        }
        if ($phoneInput === '' && $email === '') {
            $errors['phone'] = __('academy.validation.contact');
        }
        if (! filter_var($data['consent'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $errors['consent'] = __('academy.validation.consent');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $email = $email === '' ? null : $email;
        $result = DB::transaction(function () use ($cohort, $name, $email, $phone, $locale): EnrolmentResult {
            // One enrolment at a time per cohort, so the last place is never given twice.
            $locked = CourseCohort::query()->whereKey($cohort->getKey())->lockForUpdate()->firstOrFail();

            $existing = $this->existing($locked, $phone, $email);
            if ($existing !== null) {
                return new EnrolmentResult($existing, existing: true);
            }

            $status = match ($locked->enrolmentState()) {
                CourseCohort::STATE_OPEN => EnrolmentStatus::Enrolled,
                CourseCohort::STATE_WAITLIST => EnrolmentStatus::Waitlisted,
                default => throw ValidationException::withMessages(['form' => __('academy.enrol.closed')]),
            };

            $token = Str::random(40);
            /** @var Enrolment $enrolment */
            $enrolment = $locked->enrolments()->create([
                'locale' => $locale,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'phone_hash' => $phone !== null ? Enrolment::hash($phone) : null,
                'email_hash' => $email !== null ? Enrolment::hash($email) : null,
                'status' => $status,
                'token' => $token,
                'token_hash' => Enrolment::hash($token),
                'consented_at' => now(),
            ]);

            return new EnrolmentResult($enrolment, existing: false);
        });

        $enrolment = $result->enrolment;
        $this->messages->handle($enrolment, match (true) {
            $result->existing => SendEnrolmentMessage::AGAIN,
            $enrolment->status === EnrolmentStatus::Waitlisted => SendEnrolmentMessage::WAITLISTED,
            default => SendEnrolmentMessage::ENROLLED,
        });

        if (! $result->existing) {
            Recipients::notify(Recipients::withPermission('cohorts.manage'), EnrolmentNotice::for($cohort));
        }

        return $result;
    }

    private function existing(CourseCohort $cohort, ?string $phone, ?string $email): ?Enrolment
    {
        if ($phone === null && $email === null) {
            return null;
        }

        return $cohort->enrolments()->active()->whereNull('erased_at')
            ->where(function (Builder $query) use ($phone, $email): void {
                if ($phone !== null) {
                    $query->orWhere('phone_hash', Enrolment::hash($phone));
                }
                if ($email !== null) {
                    $query->orWhere('email_hash', Enrolment::hash($email));
                }
            })
            ->first();
    }
}
