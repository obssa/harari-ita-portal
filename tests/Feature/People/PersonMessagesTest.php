<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Innovation\Actions\ChallengeEntry;
use App\Domain\Innovation\Actions\JudgeChallenge;
use App\Domain\Innovation\Actions\ReviewApplication;
use App\Domain\Innovation\Actions\SaveChallenge;
use App\Domain\Innovation\Actions\SaveProgramme;
use App\Domain\Innovation\Actions\SubmitApplication;
use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Mail\ApplicationMail;
use App\Domain\Innovation\Models\Application;
use App\Domain\Innovation\Models\Challenge;
use App\Domain\Innovation\Models\ChallengeTeam;
use App\Domain\Innovation\Models\Programme;
use App\Domain\Innovation\Notifications\TeamNotice;
use App\Domain\People\Actions\MessagePerson;
use App\Domain\People\Mail\PersonMail;
use App\Domain\People\Models\PersonNote;
use App\Domain\Privacy\Actions\ErasePersonalData;
use App\Domain\Privacy\Support\PersonalDataSearch;
use App\Domain\Volunteers\Actions\SaveVolunteer;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Filament\Resources\ChallengeTeams\Pages\ViewChallengeTeam;
use App\Filament\Resources\Volunteers\Pages\ViewVolunteer;
use App\Services\Sms\SendSms;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Livewire\livewire;

/*
 * ADR-0053: staff answer applicants, teams and volunteers from the admin, and hear when teams arrive. The words are
 * kept encrypted in the person's history, never in the activity log.
 */

beforeEach(function () {
    Mail::fake();
    Bus::fake([SendSms::class]);
});

function messagedApplication(): Application
{
    $programme = app(SaveProgramme::class)->handle(new Programme, [
        'kind' => 'incubation',
        'translations' => ['en' => ['title' => 'Sample messaging programme', 'summary' => 'Sample summary.', 'published' => true]],
        'questions' => [['labels' => ['en' => 'Sample question'], 'long' => true, 'required' => true]],
        'cohorts' => [[
            'name' => 'Sample open cohort',
            'opens_at' => CarbonImmutable::now()->subDay()->toDateTimeString(),
            'closes_at' => CarbonImmutable::now()->addWeek()->toDateTimeString(),
            'places' => 10,
        ]],
    ], staff(SystemRole::InnovationOfficer));
    $key = $programme->questionsIn('en')[0]['key'];

    return app(SubmitApplication::class)->handle($programme, [
        'applicant_name' => 'Sample Applicant', 'applicant_email' => 'applicant@example.org',
        'answers' => [$key => 'Sample answer to the question.'], 'consent' => '1',
    ], 'en');
}

function messagedChallenge(): Challenge
{
    return app(SaveChallenge::class)->handle(new Challenge, [
        'translations' => ['en' => ['title' => 'Sample messaging challenge', 'summary' => 'Sample summary.', 'published' => true]],
        'registration_opens_at' => CarbonImmutable::now()->subDay()->toDateTimeString(),
        'registration_closes_at' => CarbonImmutable::now()->addDay()->toDateTimeString(),
        'submission_closes_at' => CarbonImmutable::now()->addDays(3)->toDateTimeString(),
        'max_team_size' => 3,
        'criteria' => [['labels' => ['en' => 'Impact'], 'weight' => 1]],
    ], staff(SystemRole::InnovationOfficer));
}

function messagedTeam(Challenge $challenge, string $name = 'Sample Message Team'): ChallengeTeam
{
    return app(ChallengeEntry::class)->register($challenge, [
        'name' => $name, 'contact_name' => 'Sample Contact', 'contact_email' => mb_strtolower(str_replace(' ', '', $name)).'@example.org', 'consent' => '1',
    ], 'en');
}

it('lets reviewers message an applicant, keeping the words encrypted in the history and out of the activity log', function () {
    $application = messagedApplication();
    Mail::fake();
    $this->actingAs(staff(SystemRole::SuperAdmin, mfa: true));

    livewire(ViewApplication::class, ['record' => $application->getRouteKey()])
        ->callAction('message', data: ['body' => 'Sample: please bring your plan to the interview.'])
        ->assertHasNoActionErrors();

    Mail::assertQueued(PersonMail::class, fn (PersonMail $mail): bool => $mail->hasTo('applicant@example.org')
        && $mail->body === 'Sample: please bring your plan to the interview.'
        && $mail->subjectLine === __('people.mail.message.subject', ['about' => 'Sample messaging programme'], 'en')
        && $mail->reference === $application->reference);
    $note = $application->notes()->sole();
    expect($note->kind)->toBe(PersonNote::MESSAGE)->and($note->body)->toBe('Sample: please bring your plan to the interview.');
    expect(DB::table('people_notes')->where('id', $note->id)->value('body'))->not->toContain('bring your plan')
        ->and(DB::table('activity_log')->where('properties', 'like', '%bring your plan%')->exists())->toBeFalse();

    $this->get(ApplicationResource::getUrl('view', ['record' => $application]))->assertOk()
        ->assertSee(__('people.note.kind.message', [], 'en'))
        ->assertSee('Sample: please bring your plan to the interview.');

    // An erasure removes the words with the person's details.
    app(ErasePersonalData::class)->handle(new PersonalDataSearch('applicant@example.org', null), staff(SystemRole::SuperAdmin));
    expect($note->refresh()->body)->not->toContain('bring your plan');
});

it('emails the applicant when the application moves to screening or interview', function () {
    $application = messagedApplication();
    Mail::fake();
    $reviewer = staff(SystemRole::InnovationOfficer);

    app(ReviewApplication::class)->move($application, ApplicationStage::Screening, null, $reviewer);
    app(ReviewApplication::class)->move($application, ApplicationStage::Interview, null, $reviewer);

    Mail::assertQueued(ApplicationMail::class, fn (ApplicationMail $mail): bool => $mail->kind === 'stage' && $mail->hasTo('applicant@example.org'));
    Mail::assertQueuedCount(2);
});

it('tells the challenge team about each team and its first submission, and lets them message it', function () {
    Notification::fake();
    $officer = staff(SystemRole::InnovationOfficer);
    $challenge = messagedChallenge();
    $team = messagedTeam($challenge);

    Notification::assertSentTo($officer, TeamNotice::class, fn (TeamNotice $notice): bool => $notice->event === TeamNotice::REGISTERED && $notice->teamId === $team->id);

    $entry = app(ChallengeEntry::class);
    $work = ['submission_title' => 'Sample app', 'submission_description' => 'Sample: a working prototype that uses open data.'];
    $entry->submit($team, $work);
    $entry->submit($team->refresh(), $work);
    Notification::assertSentToTimes($officer, TeamNotice::class, 2);

    $this->actingAs(staff(SystemRole::SuperAdmin, mfa: true));
    livewire(ViewChallengeTeam::class, ['record' => $team->getRouteKey()])
        ->callAction('message', data: ['body' => 'Sample: your demo slot is at ten.'])
        ->assertHasNoActionErrors();
    Mail::assertQueued(PersonMail::class, fn (PersonMail $mail): bool => $mail->hasTo('samplemessageteam@example.org') && $mail->body === 'Sample: your demo slot is at ten.');
    expect($team->notes()->sole()->kind)->toBe(PersonNote::MESSAGE);

    expect(fn () => app(MessagePerson::class)->team($team, 'Sample', staff(SystemRole::CommunicationsEditor)))->toThrow(HttpException::class);
});

it('emails every team once when the results are first published', function () {
    $challenge = messagedChallenge();
    $first = messagedTeam($challenge, 'Sample Team One');
    $second = messagedTeam($challenge, 'Sample Team Two');
    $first->forceFill(['score' => '4.50'])->save();
    Mail::fake();

    app(JudgeChallenge::class)->publish($challenge);
    app(JudgeChallenge::class)->publish($challenge->refresh());

    Mail::assertQueuedCount(2);
    Mail::assertQueued(PersonMail::class, fn (PersonMail $mail): bool => $mail->hasTo('sampleteamone@example.org')
        && str_contains($mail->body, __('innovation.mail.results.ranked', ['team' => 'Sample Team One', 'rank' => 1], 'en')));
    Mail::assertQueued(PersonMail::class, fn (PersonMail $mail): bool => $mail->hasTo('sampleteamtwo@example.org')
        && ! str_contains($mail->body, 'Sample Team Two'));
    expect($second->refresh()->rank)->toBeNull();
});

it('thanks a new volunteer and lets the coordinator message them by text when there is only a phone', function () {
    $volunteer = app(SaveVolunteer::class)->signUp([
        'name' => 'Sample Volunteer', 'phone' => '0911 22 33 44', 'skills' => ['mentoring'], 'availability' => ['weekends'], 'languages' => ['am'], 'consent' => '1',
    ], 'en');
    Bus::assertDispatched(SendSms::class, fn (SendSms $sms): bool => $sms->to === '+251911223344' && $sms->text === __('volunteers.sms.received', [], 'en'));

    app(SaveVolunteer::class)->signUp(['name' => 'Sample Emailing Volunteer', 'phone' => '0911 22 33 55', 'email' => 'volunteer@example.org', 'skills' => ['mentoring'], 'availability' => ['weekends'], 'languages' => ['am'], 'consent' => '1'], 'en');
    Mail::assertQueued(PersonMail::class, fn (PersonMail $mail): bool => $mail->hasTo('volunteer@example.org') && $mail->body === __('volunteers.mail.received.body', [], 'en'));

    $this->actingAs(staff(SystemRole::SuperAdmin, mfa: true));
    livewire(ViewVolunteer::class, ['record' => $volunteer->getRouteKey()])
        ->callAction('message', data: ['body' => 'Sample: can you help on Saturday?'])
        ->assertHasNoActionErrors();
    Bus::assertDispatched(SendSms::class, fn (SendSms $sms): bool => $sms->to === '+251911223344' && str_contains($sms->text, 'Sample: can you help on Saturday?'));
    expect($volunteer->notes()->where('kind', PersonNote::MESSAGE)->count())->toBe(1);

    expect(fn () => app(MessagePerson::class)->volunteer($volunteer, str_repeat('a', MessagePerson::MAX_SMS + 1), staff(SystemRole::SuperAdmin)))
        ->toThrow(ValidationException::class);
});
