<?php

use App\Domain\Academy\Actions\EnrolInCohort;
use App\Domain\Academy\Actions\IssueCertificate;
use App\Domain\Academy\Actions\IssueCohortCertificates;
use App\Domain\Academy\Actions\MarkAttendance;
use App\Domain\Academy\Actions\RevokeCertificate;
use App\Domain\Academy\Actions\SaveInstructor;
use App\Domain\Academy\Models\Certificate;
use App\Domain\Academy\Models\Course;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\Enrolment;
use App\Domain\Academy\Models\Instructor;
use App\Domain\Access\Enums\SystemRole;
use App\Domain\Privacy\Actions\ErasePersonalData;
use App\Domain\Privacy\Actions\ExportPersonalData;
use App\Domain\Privacy\Support\PersonalDataSearch;
use App\Filament\Resources\CourseCohorts\Pages\EditCourseCohort;
use App\Filament\Resources\CourseCohorts\Pages\ListCourseCohorts;
use App\Filament\Resources\CourseCohorts\Pages\ViewCourseCohort;
use App\Filament\Resources\CourseCohorts\RelationManagers\SessionsRelationManager;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Services\Sms\SendSms;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Mail::fake();
    Bus::fake([SendSms::class]);
});

/**
 * A cohort that ran last week with three sessions, and its learners.
 *
 * @return array{0: CourseCohort, 1: list<Enrolment>}
 */
function finishedCohort(int $learners = 2): array
{
    $cohort = sampleCohort(sampleCourse(), ['enrol_opens_at' => CarbonImmutable::now()->subMonth()->toDateTimeString()]);
    $enrolments = [];
    for ($i = 1; $i <= $learners; $i++) {
        $enrolments[] = app(EnrolInCohort::class)->handle($cohort, ['name' => "Sample Learner{$i} Person", 'phone' => '0911 000 5'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'consent' => true], 'en')->enrolment;
    }
    // The cohort then ran: move its dates and sessions into the past.
    $start = CarbonImmutable::today()->subWeek();
    $cohort->forceFill(['starts_on' => $start, 'ends_on' => $start->addDays(2)])->save();
    foreach ($cohort->sessions as $i => $session) {
        $session->forceFill(['starts_at' => $start->addDays($i)->setTime(9, 0), 'ends_at' => $start->addDays($i)->setTime(12, 0)])->save();
    }

    return [$cohort->refresh(), array_map(fn (Enrolment $e): Enrolment => $e->fresh() ?? $e, $enrolments)];
}

it('marks attendance per session for instructors of the cohort only, and never for a session still to come', function () {
    [$cohort, [$a, $b]] = finishedCohort();
    $teacher = staff(SystemRole::Instructor, mfa: false);
    $other = staff(SystemRole::Instructor, mfa: false);
    $instructor = app(SaveInstructor::class)->handle(new Instructor, ['name' => 'Sample Instructor', 'user_id' => $teacher->id], staff(SystemRole::AcademyCoordinator));
    $cohort->instructors()->sync([$instructor->id]);
    [$first, $second] = $cohort->sessions->all();

    expect(app(MarkAttendance::class)->handle($first, [$a->id, $b->id, 'not-an-enrolment'], $teacher))->toBe(2)
        ->and(app(MarkAttendance::class)->handle($first, [$a->id], $teacher))->toBe(1)
        ->and($first->attendances()->pluck('enrolment_id')->all())->toBe([$a->id]);
    app(MarkAttendance::class)->handle($second, [$a->id], $teacher);

    expect(fn () => app(MarkAttendance::class)->handle($first, [$b->id], $other))->toThrow(AuthorizationException::class);

    $future = sampleCohort(sampleCourse('Sample future course'));
    expect(fn () => app(MarkAttendance::class)->handle($future->sessions->first(), [], staff(SystemRole::AcademyCoordinator)))->toThrow(ValidationException::class);

    // The instructor sees only the cohorts they teach, read-only, and marks attendance there.
    $this->actingAs($teacher);
    livewire(ListCourseCohorts::class)->removeTableFilters()->assertCanSeeTableRecords([$cohort])->assertCanNotSeeTableRecords([$future]);
    livewire(ViewCourseCohort::class, ['record' => $cohort->getRouteKey()])->assertOk();
    $this->get(EditCourseCohort::getUrl(['record' => $cohort]))->assertForbidden();
    livewire(SessionsRelationManager::class, ['ownerRecord' => $cohort, 'pageClass' => ViewCourseCohort::class])
        ->assertOk()
        ->callTableAction('attendance', $second, data: ['present' => [$a->id, $b->id]])
        ->assertHasNoTableActionErrors();
    expect($second->attendances()->count())->toBe(2);
});

it('issues certificates to those who met the attendance rule, with a random code, a signature and the initials', function () {
    [$cohort, [$a, $b]] = finishedCohort();
    $coordinator = staff(SystemRole::AcademyCoordinator);
    foreach ($cohort->sessions as $session) {
        app(MarkAttendance::class)->handle($session, [$a->id], $coordinator);
    }
    app(MarkAttendance::class)->handle($cohort->sessions->first(), [$a->id, $b->id], $coordinator);

    // 3 of 3 for the first learner; 1 of 3 for the second, under the 80% rule.
    expect(app(IssueCohortCertificates::class)->handle($cohort, $coordinator))->toBe(1)
        ->and(app(IssueCohortCertificates::class)->handle($cohort, $coordinator))->toBe(0);

    $certificate = Certificate::query()->sole();
    expect($certificate->enrolment_id)->toBe($a->id)
        ->and($certificate->code)->toMatch('/^'.Certificate::PATTERN.'$/')
        ->and($certificate->holder_initials)->toBe('S. L.')
        ->and($certificate->holder_name)->toBe('Sample Learner1 Person')
        ->and($certificate->isValid())->toBeTrue();
    Bus::assertDispatched(SendSms::class, fn (SendSms $sms): bool => str_contains($sms->text, '/academy/enrolment/'));

    // Staff may still issue one by one; an Instructor may not issue at all.
    expect(fn () => app(IssueCertificate::class)->handle($b, staff(SystemRole::Instructor, mfa: false)))->toThrow(AuthorizationException::class);
    expect(app(IssueCertificate::class)->handle($b, $coordinator)->code)->not->toBe($certificate->code)
        ->and(fn () => app(IssueCertificate::class)->handle($b, $coordinator))->toThrow(ValidationException::class);

    // A row changed outside the portal no longer verifies.
    DB::table('certificates')->where('id', $certificate->id)->update(['holder_initials' => 'X. Y.']);
    expect($certificate->refresh()->isValid())->toBeFalse();
});

it('verifies a certificate by code or QR with initials only, and answers revoked or not found', function () {
    [$cohort, [$a]] = finishedCohort(1);
    $coordinator = staff(SystemRole::AcademyCoordinator);
    $certificate = app(IssueCertificate::class)->handle($a, $coordinator);

    $this->get('/en/academy/certificates/verify?code='.strtolower(str_replace('-', '', $certificate->code)))
        ->assertRedirect(route('certificates.verify.show', ['locale' => 'en', 'code' => $certificate->code]));
    $this->get($certificate->verifyUrl('en'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee(__('academy.verify.valid', [], 'en'))
        ->assertSee('S. L.')
        ->assertSee('Sample digital basics')
        ->assertDontSee('Sample Learner1');

    // The learner prints it from their page, with the QR code of the verification address.
    $this->get(route('enrolments.certificate', ['locale' => 'en', 'token' => (string) $a->token]))
        ->assertOk()
        ->assertSee('Sample Learner1 Person')
        ->assertSee($certificate->code)
        ->assertSee('data:image/svg+xml;base64,', escape: false);

    app(RevokeCertificate::class)->handle($certificate, 'Sample reason: issued in error', $coordinator);
    $this->get($certificate->verifyUrl('en'))->assertOk()->assertSee(__('academy.verify.revoked', [], 'en'))->assertDontSee('S. L.')->assertDontSee('Sample reason');
    $this->get(route('enrolments.certificate', ['locale' => 'en', 'token' => (string) $a->token]))->assertNotFound();
    expect(fn () => app(RevokeCertificate::class)->handle($certificate->refresh(), 'Again', $coordinator))->toThrow(AuthorizationException::class);

    $this->get('/en/academy/certificates/verify/CRT-2222-2222')->assertNotFound()->assertSee(__('academy.verify.unknown', [], 'en'));
    $this->get('/en/academy/certificates/verify?code=nonsense')->assertStatus(422);
});

it('prints a cohort\'s certificates for staff who issue them, and no one else', function () {
    [$cohort, [$a, $b]] = finishedCohort();
    $coordinator = staff(SystemRole::AcademyCoordinator);
    app(IssueCertificate::class)->handle($a, $coordinator);
    app(IssueCertificate::class)->handle($b, $coordinator);

    $url = route('filament.admin.academy.certificates.print', ['cohort' => $cohort->id]);
    $this->actingAs($coordinator)->get($url)->assertOk()->assertSee('Sample Learner1 Person')->assertSee('Sample Learner2 Person');
    $this->actingAs(staff(SystemRole::Instructor, mfa: false))->get($url)->assertForbidden();
});

it('lets a coordinator create a course and edit a cohort in the admin', function () {
    $coordinator = staff(SystemRole::AcademyCoordinator);
    $this->actingAs($coordinator);

    livewire(CreateCourse::class)
        ->fillForm(['translations' => ['en' => ['title' => 'Sample admin course', 'published' => true]], 'level' => 'intermediate', 'mode' => 'online', 'attendance_required' => 75])
        ->call('create')
        ->assertHasNoFormErrors();
    $course = Course::query()->latest('created_at')->firstOrFail();
    expect($course->attendance_required)->toBe(75);
    livewire(EditCourse::class, ['record' => $course->getRouteKey()])->assertOk()->assertFormSet(['level' => 'intermediate']);

    $cohort = sampleCohort($course);
    livewire(EditCourseCohort::class, ['record' => $cohort->getRouteKey()])
        ->assertOk()
        ->fillForm(['capacity' => 30])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($cohort->refresh()->capacity)->toBe(30)->and($cohort->sessions()->count())->toBe(3);
});

it('finds, exports and erases a learner\'s data, revoking their certificates', function () {
    [$cohort, [$a]] = finishedCohort(1);
    $coordinator = staff(SystemRole::AcademyCoordinator);
    $certificate = app(IssueCertificate::class)->handle($a, $coordinator);
    $admin = staff(SystemRole::Admin);

    $search = new PersonalDataSearch(null, '0911000501');
    expect($search->counts()['enrolments'])->toBe(1);
    $export = app(ExportPersonalData::class)->handle($search, $admin);
    expect($export['academy_enrolments'][0]['certificates'][0]['code'])->toBe($certificate->code);

    app(ErasePersonalData::class)->handle($search, $admin);
    expect($a->refresh()->name)->toBeNull()->and($a->erased_at)->not->toBeNull()
        ->and($certificate->refresh()->isRevoked())->toBeTrue()->and($certificate->holder_name)->toBeNull()
        ->and((new PersonalDataSearch(null, '0911000501'))->counts()['enrolments'])->toBe(0);
});

it('deletes uncertified enrolments and anonymises certified ones after the retention period', function () {
    [$cohort, [$a, $b]] = finishedCohort();
    app(IssueCertificate::class)->handle($a, staff(SystemRole::AcademyCoordinator));
    $cohort->forceFill(['starts_on' => CarbonImmutable::today()->subYears(3), 'ends_on' => CarbonImmutable::today()->subYears(3)->addDays(2)])->save();

    $this->artisan('academy:purge')->assertSuccessful();

    expect(Enrolment::query()->whereKey($b->id)->exists())->toBeFalse()
        ->and($a->refresh()->phone)->toBeNull()
        ->and($a->erased_at)->not->toBeNull()
        ->and(Certificate::query()->sole()->holder_name)->toBe('Sample Learner1 Person');
});
