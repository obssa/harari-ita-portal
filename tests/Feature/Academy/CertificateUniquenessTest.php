<?php

use App\Domain\Academy\Actions\IssueCertificate;
use App\Domain\Academy\Actions\IssueCohortCertificates;
use App\Domain\Academy\Actions\RevokeCertificate;
use App\Domain\Academy\Models\Certificate;
use App\Domain\Academy\Models\Enrolment;
use App\Domain\Access\Enums\SystemRole;
use App\Services\Sms\SendSms;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/*
 * One valid certificate per enrolment (ADR-0030), held by the locked enrolment and by a unique index on the valid
 * ones; after a revocation a new certificate can be issued.
 */

beforeEach(function () {
    Mail::fake();
    Bus::fake([SendSms::class]);
});

it('issues one certificate even when the same learner is issued twice from stale screens', function () {
    [$cohort, [$learner]] = finishedCohort(1);
    $coordinator = staff(SystemRole::AcademyCoordinator);
    $stale = Enrolment::query()->findOrFail($learner->id);

    app(IssueCertificate::class)->handle($learner, $coordinator, notify: false);
    expect(fn () => app(IssueCertificate::class)->handle($stale, $coordinator, notify: false))->toThrow(ValidationException::class);
    expect(app(IssueCohortCertificates::class)->handle($cohort, $coordinator))->toBe(0)
        ->and(Certificate::query()->where('enrolment_id', $learner->id)->count())->toBe(1);
});

it('lets the database refuse a second valid certificate, and allows a new one after a revocation', function () {
    [, [$learner]] = finishedCohort(1);
    $coordinator = staff(SystemRole::AcademyCoordinator);
    $first = app(IssueCertificate::class)->handle($learner, $coordinator, notify: false);

    $copy = $first->replicate(['valid']);
    $copy->code = Certificate::newCode();
    expect(fn () => $copy->save())->toThrow(QueryException::class);

    app(RevokeCertificate::class)->handle($first, 'Sample reason: wrong name', $coordinator);
    $second = app(IssueCertificate::class)->handle($learner->refresh(), $coordinator, notify: false);

    expect($second->isValid())->toBeTrue()
        ->and($first->refresh()->isRevoked())->toBeTrue()
        ->and(Certificate::query()->where('enrolment_id', $learner->id)->count())->toBe(2);
});
