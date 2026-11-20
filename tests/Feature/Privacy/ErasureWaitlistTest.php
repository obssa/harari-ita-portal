<?php

use App\Domain\Academy\Actions\EnrolInCohort;
use App\Domain\Academy\Actions\FillCohortFromWaitlist;
use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Access\Enums\SystemRole;
use App\Domain\Events\Actions\FillFromWaitlist;
use App\Domain\Events\Enums\RegistrationStatus;
use App\Domain\Privacy\Actions\ErasePersonalData;
use App\Domain\Privacy\Support\PersonalDataSearch;
use App\Services\Sms\SendSms;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

/*
 * An erased person leaves every waiting list and gives back a place that has not been used yet, so the next person
 * moves up; an erased row is never promoted.
 */

beforeEach(function () {
    Mail::fake();
    Bus::fake([SendSms::class]);
});

it('gives back an erased learner\'s place in a cohort that has not started, and moves the next person up', function () {
    $cohort = sampleCohort(sampleCourse(), ['capacity' => 1, 'waitlist' => true]);
    $enrol = fn (string $name, string $email) => app(EnrolInCohort::class)->handle($cohort, ['name' => $name, 'email' => $email, 'consent' => true], 'en')->enrolment;
    $first = $enrol('Sample First Learner', 'first@example.org');
    $second = $enrol('Sample Second Learner', 'second@example.org');
    $third = $enrol('Sample Third Learner', 'third@example.org');
    expect($second->status)->toBe(EnrolmentStatus::Waitlisted);

    // The second, waiting, is erased: they leave the list and are never promoted.
    app(ErasePersonalData::class)->handle(new PersonalDataSearch('second@example.org', null), staff(SystemRole::SuperAdmin));
    expect($second->refresh()->status)->toBe(EnrolmentStatus::Cancelled)
        ->and($second->erased_at)->not->toBeNull();

    // The first, enrolled, is erased before the start: the place goes to the third, not to the erased second.
    app(ErasePersonalData::class)->handle(new PersonalDataSearch('first@example.org', null), staff(SystemRole::SuperAdmin));
    expect($first->refresh()->status)->toBe(EnrolmentStatus::Cancelled)
        ->and($third->refresh()->status)->toBe(EnrolmentStatus::Enrolled)
        ->and($cohort->refresh()->enrolledCount())->toBe(1);
});

it('keeps an erased learner\'s place in a cohort that already began, as anonymous history', function () {
    $cohort = sampleCohort(sampleCourse());
    $enrolment = app(EnrolInCohort::class)->handle($cohort, ['name' => 'Sample Started Learner', 'email' => 'started@example.org', 'consent' => true], 'en')->enrolment;
    $cohort->forceFill(['starts_on' => now()->subDay()->toDateString(), 'ends_on' => now()->addDay()->toDateString()])->save();

    app(ErasePersonalData::class)->handle(new PersonalDataSearch('started@example.org', null), staff(SystemRole::SuperAdmin));

    expect($enrolment->refresh()->status)->toBe(EnrolmentStatus::Enrolled)->and($enrolment->erased_at)->not->toBeNull();
});

it('never promotes an erased row already on a waiting list', function () {
    $cohort = sampleCohort(sampleCourse(), ['capacity' => 1, 'waitlist' => true]);
    $enrol = fn (string $name, string $email) => app(EnrolInCohort::class)->handle($cohort, ['name' => $name, 'email' => $email, 'consent' => true], 'en')->enrolment;
    $first = $enrol('Sample Holder', 'holder@example.org');
    $ghost = $enrol('Sample Ghost', 'ghost@example.org');
    // An erasure from before this rule left the row waiting.
    $ghost->forceFill(['name' => null, 'email' => null, 'email_hash' => null, 'erased_at' => now()])->saveQuietly();
    $first->forceFill(['status' => EnrolmentStatus::Cancelled])->save();

    expect(app(FillCohortFromWaitlist::class)->handle($cohort))->toBe([])
        ->and($ghost->refresh()->status)->toBe(EnrolmentStatus::Waitlisted);
});

it('does the same for event registrations', function () {
    $event = registrationEvent(['capacity' => 1, 'waitlist' => true]);
    $holder = register($event, ['name' => 'Sample Holder', 'email' => 'event-holder@example.org']);
    $waiting = register($event, ['name' => 'Sample Waiting', 'email' => 'event-waiting@example.org']);
    $ghost = register($event, ['name' => 'Sample Ghost', 'email' => 'event-ghost@example.org']);
    $ghost->forceFill(['name' => '—', 'email' => null, 'email_hash' => null, 'erased_at' => now()])->saveQuietly();
    expect($waiting->status)->toBe(RegistrationStatus::Waitlisted);

    // Moving the waiting row behind the ghost shows that the ghost is skipped, not just later in the queue.
    $waiting->forceFill(['created_at' => now()->addMinute()])->saveQuietly();
    app(ErasePersonalData::class)->handle(new PersonalDataSearch('event-holder@example.org', null), staff(SystemRole::SuperAdmin));

    expect($holder->refresh()->status)->toBe(RegistrationStatus::Cancelled)
        ->and($waiting->refresh()->status)->toBe(RegistrationStatus::Registered)
        ->and($ghost->refresh()->status)->toBe(RegistrationStatus::Waitlisted)
        ->and(app(FillFromWaitlist::class)->handle($event))->toBe([]);
});
