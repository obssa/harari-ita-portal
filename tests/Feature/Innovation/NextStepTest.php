<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Events\Actions\RegisterForEvent;
use App\Domain\Events\Actions\SaveEvent;
use App\Domain\Events\Models\Event;
use App\Domain\Innovation\Actions\ReviewStartup;
use App\Domain\Innovation\Actions\SaveChallenge;
use App\Domain\Innovation\Actions\SaveProgramme;
use App\Domain\Innovation\Enums\ListingStatus;
use App\Domain\Innovation\Models\Challenge;
use App\Domain\Innovation\Models\Programme;
use App\Domain\Innovation\Models\Startup;
use App\Support\SpamGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

/*
 * ADR-0053: every detail page offers a next step, never a dead end.
 */

beforeEach(fn () => Mail::fake());

function nextStepEvent(bool $waitlist): Event
{
    $event = app(SaveEvent::class)->handle(new Event, [
        'starts_at' => CarbonImmutable::now()->addDays(5)->setTime(9, 0)->toDateTimeString(),
        'registration_enabled' => true,
        'capacity' => 1,
        'waitlist' => $waitlist,
        'translations' => ['en' => ['title' => 'Sample next step forum', 'venue' => 'Sample hall', 'published' => true]],
    ], staff(SystemRole::CommunicationsEditor));
    app(RegisterForEvent::class)->handle($event, ['name' => 'Sample One', 'email' => 'one@example.org', 'consent' => true], 'en');

    return $event->refresh();
}

it('offers updates and a question on a programme with no open cohort, and the mentor sign-up', function () {
    $programme = app(SaveProgramme::class)->handle(new Programme, [
        'kind' => 'incubation',
        'translations' => ['en' => ['title' => 'Sample closed programme', 'summary' => 'Sample summary.', 'published' => true]],
        'cohorts' => [[
            'name' => 'Sample past cohort',
            'opens_at' => CarbonImmutable::now()->subMonth()->toDateTimeString(),
            'closes_at' => CarbonImmutable::now()->subWeek()->toDateTimeString(),
            'places' => 10,
        ]],
    ], staff(SystemRole::InnovationOfficer));
    $slug = $programme->translation('en')->slug;

    $this->get('/en/innovation/programmes/'.$slug)->assertOk()
        ->assertSee(__('innovation.next_step.subscribe', [], 'en'))
        ->assertSee(__('innovation.next_step.ask', [], 'en'))
        ->assertSee('/en/newsletter', escape: false)
        ->assertSee('/en/contact', escape: false)
        ->assertSee('/en/volunteer', escape: false)
        ->assertDontSee('/en/innovation/programmes/'.$slug.'/apply', escape: false);
});

it('leads teams to send their work while a challenge is in the building phase', function () {
    $challenge = app(SaveChallenge::class)->handle(new Challenge, [
        'translations' => ['en' => ['title' => 'Sample building challenge', 'summary' => 'Sample summary.', 'published' => true]],
        'registration_opens_at' => CarbonImmutable::now()->subWeek()->toDateTimeString(),
        'registration_closes_at' => CarbonImmutable::now()->subDay()->toDateTimeString(),
        'submission_closes_at' => CarbonImmutable::now()->addDays(3)->toDateTimeString(),
        'max_team_size' => 3,
        'criteria' => [['labels' => ['en' => 'Impact'], 'weight' => 1]],
    ], staff(SystemRole::InnovationOfficer));
    $slug = $challenge->translation('en')->slug;

    $this->get('/en/innovation/challenges/'.$slug)->assertOk()
        ->assertSee(__('innovation.challenges.send_submission', [], 'en'))
        ->assertSee('/en/innovation/teams', escape: false)
        ->assertSee('/en/volunteer', escape: false)
        ->assertDontSee('/en/innovation/challenges/'.$slug.'/register', escape: false);
});

it('says a full event is full, and offers the waiting list when there is one', function () {
    $full = nextStepEvent(waitlist: false);
    expect($full->registrationState())->toBe(Event::REGISTRATION_FULL);
    $slug = $full->translation('en')->slug;
    $this->get('/en/events/'.$slug)->assertOk()
        ->assertSee(__('events.registration.full', [], 'en'))
        ->assertDontSee('/en/events/'.$slug.'/register', escape: false);

    $full->forceDelete();
    $waitlist = nextStepEvent(waitlist: true);
    expect($waitlist->registrationState())->toBe(Event::REGISTRATION_WAITLIST);
    $slug = $waitlist->translation('en')->slug;
    $this->get('/en/events/'.$slug)->assertOk()
        ->assertSee(__('events.registration.join_waitlist', [], 'en'))
        ->assertSee('/en/events/'.$slug.'/register', escape: false);
});

it('lets founders list a startup or ask for a change from a profile', function () {
    $this->post('/en/innovation/startups/apply', [
        'name' => 'Sample Next Step Startup',
        'summary' => 'Sample: a startup that builds booking software for guesthouses in Harar.',
        'stage' => 'mvp',
        'founder_name' => 'Sample Founder',
        'founder_email' => 'founder@example.org',
        'consent' => '1',
        SpamGuard::STARTED => Crypt::encryptString((string) (time() - 10)),
    ])->assertRedirect();
    $startup = Startup::query()->sole();
    app(ReviewStartup::class)->decide($startup, ListingStatus::Listed, null, staff(SystemRole::InnovationOfficer));

    $this->get('/en/innovation/startups/'.$startup->translation('en')->slug)->assertOk()
        ->assertSee(__('innovation.startups.list_title', [], 'en'))
        ->assertSee(__('innovation.startups.claim', [], 'en'))
        ->assertSee('/en/innovation/startups/apply', escape: false)
        ->assertSee('/en/contact', escape: false);
});
