<?php

use App\Domain\Academy\Actions\CancelCohort;
use App\Domain\Academy\Actions\CancelEnrolment;
use App\Domain\Academy\Actions\EnrolInCohort;
use App\Domain\Academy\Actions\SaveCohort;
use App\Domain\Academy\Actions\SendEnrolmentMessage;
use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Mail\EnrolmentMail;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\Enrolment;
use App\Domain\Access\Enums\SystemRole;
use App\Services\Sms\SendSms;
use App\Support\SpamGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Mail::fake();
    Bus::fake([SendSms::class]);
});

function academyStamp(): string
{
    return Crypt::encryptString((string) (time() - 10));
}

/**
 * @param  array<string, mixed>  $data
 */
function enrol(CourseCohort $cohort, array $data = []): Enrolment
{
    return app(EnrolInCohort::class)->handle($cohort, ['name' => 'Sample Learner', 'phone' => '0911 000 101', 'consent' => true, ...$data], 'en')->enrolment;
}

it('enrols first come first served, keeps a waiting list when full, and enrols each contact once', function () {
    $cohort = sampleCohort(sampleCourse(), ['capacity' => 2]);

    $first = enrol($cohort, ['phone' => '0911 000 101']);
    $second = enrol($cohort, ['phone' => '0911 000 102', 'email' => 'sample.two@example.org']);
    $third = enrol($cohort, ['phone' => '0911 000 103']);

    expect($first->status)->toBe(EnrolmentStatus::Enrolled)
        ->and($second->status)->toBe(EnrolmentStatus::Enrolled)
        ->and($third->status)->toBe(EnrolmentStatus::Waitlisted)
        ->and($third->waitlistPosition())->toBe(1)
        ->and($cohort->refresh()->enrolmentState())->toBe(CourseCohort::STATE_WAITLIST);

    // The same email again: no new enrolment, the link goes again to the details on file.
    $again = app(EnrolInCohort::class)->handle($cohort, ['name' => 'Someone Else', 'email' => 'SAMPLE.TWO@example.org', 'consent' => true], 'en');
    expect($again->existing)->toBeTrue()->and($again->enrolment->id)->toBe($second->id)
        ->and(Enrolment::query()->count())->toBe(3);

    Mail::assertQueued(EnrolmentMail::class, fn (EnrolmentMail $mail): bool => $mail->kind === SendEnrolmentMessage::AGAIN);
    Bus::assertDispatched(SendSms::class);

    // The personal details are encrypted at rest.
    $raw = DB::table('enrolments')->where('id', $first->id)->first();
    expect($raw->name)->not->toContain('Sample Learner')->and($raw->phone)->not->toContain('911000101');

    // Without a waiting list, a full cohort takes no one.
    $closed = sampleCohort(sampleCourse('Sample closed course'), ['capacity' => 1, 'waitlist' => false]);
    enrol($closed);
    expect($closed->refresh()->enrolmentState())->toBe(CourseCohort::STATE_FULL)
        ->and(fn () => enrol($closed, ['phone' => '0911 000 109']))->toThrow(ValidationException::class);
});

it('checks the details and consent, and takes no one before the window opens or after it closes', function () {
    $cohort = sampleCohort(sampleCourse());

    expect(fn () => enrol($cohort, ['name' => 'S']))->toThrow(ValidationException::class)
        ->and(fn () => enrol($cohort, ['phone' => '', 'email' => '']))->toThrow(ValidationException::class)
        ->and(fn () => enrol($cohort, ['phone' => '123']))->toThrow(ValidationException::class)
        ->and(fn () => enrol($cohort, ['consent' => false]))->toThrow(ValidationException::class);

    $soon = sampleCohort(sampleCourse('Sample later course'), ['enrol_opens_at' => CarbonImmutable::now()->addDay()->toDateTimeString()]);
    expect($soon->enrolmentState())->toBe(CourseCohort::STATE_SOON)
        ->and(fn () => enrol($soon))->toThrow(ValidationException::class);

    $started = sampleCohort(sampleCourse('Sample running course'), ['starts_on' => CarbonImmutable::yesterday()->toDateString(), 'ends_on' => CarbonImmutable::tomorrow()->toDateString(), 'sessions' => []]);
    expect($started->enrolmentState())->toBe(CourseCohort::STATE_CLOSED);
});

it('moves the waiting list up when a place is given back or the places are raised, and tells everyone when a cohort is cancelled', function () {
    $cohort = sampleCohort(sampleCourse(), ['capacity' => 1]);
    $first = enrol($cohort, ['phone' => '0911 000 201']);
    $second = enrol($cohort, ['phone' => '0911 000 202']);
    $third = enrol($cohort, ['phone' => '0911 000 203']);

    expect(app(CancelEnrolment::class)->handle($first))->toBeTrue();
    expect($second->refresh()->status)->toBe(EnrolmentStatus::Enrolled)
        ->and($second->promoted_at)->not->toBeNull()
        ->and($third->refresh()->status)->toBe(EnrolmentStatus::Waitlisted);
    Mail::assertNothingQueued();
    Bus::assertDispatched(SendSms::class, fn (SendSms $sms): bool => str_contains($sms->text, SendEnrolmentMessage::learnerUrl($second)));

    app(SaveCohort::class)->handle($cohort->refresh(), [...$cohort->only(['name', 'starts_on', 'ends_on', 'enrol_opens_at']), 'mode' => 'in_person', 'capacity' => 5], staff(SystemRole::AcademyCoordinator));
    expect($third->refresh()->status)->toBe(EnrolmentStatus::Enrolled);

    expect(app(CancelCohort::class)->handle($cohort->refresh()))->toBe(2)
        ->and($cohort->refresh()->enrolmentState())->toBe(CourseCohort::STATE_CANCELLED)
        ->and($third->refresh()->status)->toBe(EnrolmentStatus::Cancelled);
});

it('enrols from the public form and shows the learner their private page, where they may give the place back', function () {
    $course = sampleCourse();
    $cohort = sampleCohort($course, ['capacity' => 10]);

    $this->get('/en/academy/courses/sample-digital-basics')
        ->assertOk()
        ->assertSee(route('enrolments.create', ['locale' => 'en', 'slug' => 'sample-digital-basics', 'cohort' => $cohort->id]), escape: false);

    $this->get("/en/academy/courses/sample-digital-basics/enrol/{$cohort->id}")
        ->assertOk()
        ->assertSee('name="'.SpamGuard::HONEYPOT.'"', escape: false)
        ->assertSee(trans_choice('academy.cohort.places_left', 10, ['count' => 10], 'en'));

    $response = $this->post("/en/academy/courses/sample-digital-basics/enrol/{$cohort->id}", [
        'name' => 'Sample Visitor', 'phone' => '0911 000 301', 'consent' => '1', SpamGuard::STARTED => academyStamp(),
    ]);
    $enrolment = Enrolment::query()->firstOrFail();
    $url = route('enrolments.show', ['locale' => 'en', 'token' => (string) $enrolment->token]);
    $response->assertRedirect($url);

    $this->get($url)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('Sample Visitor')
        ->assertSee(__('academy.learner.cancel', [], 'en'));

    $this->post(route('enrolments.cancel', ['locale' => 'en', 'token' => (string) $enrolment->token]))->assertRedirect($url);
    expect($enrolment->refresh()->status)->toBe(EnrolmentStatus::Cancelled);

    $this->get('/en/academy/enrolment/'.str_repeat('a', 40))->assertNotFound();
    $this->get('/en/academy/courses/sample-digital-basics/enrol/999999')->assertNotFound();
});

it('reminds enrolled learners once the day before the cohort starts', function () {
    $cohort = sampleCohort(sampleCourse(), [
        'starts_on' => CarbonImmutable::tomorrow()->toDateString(), 'ends_on' => CarbonImmutable::tomorrow()->addDay()->toDateString(), 'sessions' => [],
    ]);
    $learner = enrol($cohort, ['email' => 'sample.learner@example.org']);
    enrol($cohort, ['phone' => '0911 000 401', 'consent' => true]);
    Mail::fake();

    $this->artisan('academy:send-reminders')->assertSuccessful();
    $this->artisan('academy:send-reminders')->assertSuccessful();

    Mail::assertQueued(EnrolmentMail::class, 1);
    expect($learner->refresh()->reminded_at)->not->toBeNull();
});
