<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Innovation\Actions\ChallengeEntry;
use App\Domain\Innovation\Actions\JudgeChallenge;
use App\Domain\Innovation\Actions\SaveChallenge;
use App\Domain\Innovation\Actions\SaveProgramme;
use App\Domain\Innovation\Mail\TeamRegisteredMail;
use App\Domain\Innovation\Models\Challenge;
use App\Domain\Innovation\Models\ChallengeTeam;
use App\Domain\Innovation\Models\JudgeAssignment;
use App\Domain\Innovation\Models\Programme;
use App\Domain\Innovation\Models\Startup;
use App\Domain\Innovation\Queries\OpenCalls;
use App\Domain\Privacy\Actions\ErasePersonalData;
use App\Domain\Privacy\Support\PersonalDataSearch;
use App\Filament\Resources\Challenges\Pages\CreateChallenge;
use App\Filament\Resources\JudgeAssignments\Pages\ViewJudgeAssignment;
use App\Support\SpamGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

/**
 * @param  array<string, mixed>  $data
 */
function sampleChallenge(array $data = []): Challenge
{
    return app(SaveChallenge::class)->handle(new Challenge, [
        'translations' => [
            'en' => ['title' => 'Sample open data hackathon', 'summary' => 'Sample: build with the region\'s open data.', 'prizes' => "Sample first prize\nSample second prize", 'rules' => 'Sample rule: original work only.', 'published' => true],
            'am' => ['title' => 'ናሙና የክፍት መረጃ ሃካቶን', 'published' => true],
        ],
        'registration_opens_at' => CarbonImmutable::now()->subDay()->toDateTimeString(),
        'registration_closes_at' => CarbonImmutable::now()->addDay()->toDateTimeString(),
        'submission_closes_at' => CarbonImmutable::now()->addDays(3)->toDateTimeString(),
        'max_team_size' => 3,
        'criteria' => [
            ['labels' => ['en' => 'Impact', 'am' => 'ተጽዕኖ'], 'weight' => 3],
            ['labels' => ['en' => 'Use of data'], 'weight' => 1],
        ],
        ...$data,
    ], staff(SystemRole::InnovationOfficer));
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function teamForm(array $overrides = []): array
{
    return [
        'name' => 'Sample Team Alpha',
        'contact_name' => 'Sample Contact',
        'contact_email' => 'team@example.org',
        'contact_phone' => '0911223344',
        'members' => "Sample Member One\nSample Member Two",
        'show_in_results' => '1',
        'consent' => '1',
        SpamGuard::STARTED => Crypt::encryptString((string) (time() - 10)),
        ...$overrides,
    ];
}

function submitted(Challenge $challenge, string $name, string $email): ChallengeTeam
{
    $entry = app(ChallengeEntry::class);
    $team = $entry->register($challenge, ['name' => $name, 'contact_name' => 'Sample Contact', 'contact_email' => $email, 'consent' => '1', 'show_in_results' => $name !== 'Sample Hidden Team'], 'en');

    return $entry->submit($team, ['submission_title' => "{$name} app", 'submission_description' => 'Sample: a working prototype that uses open data.', 'submission_links' => ['https://example.org/demo']]);
}

it('shows a challenge with its phases, countdown, prizes and weighted rubric in its languages', function () {
    $challenge = sampleChallenge();
    $slug = $challenge->translation('en')->slug;

    expect($challenge->phase())->toBe(Challenge::PHASE_REGISTRATION)
        ->and($challenge->criteriaIn('en'))->toHaveCount(2)
        ->and($challenge->criteriaIn('en')[0]['key'])->toBe('impact')
        ->and($challenge->criteriaIn('am')[0]['label'])->toBe('ተጽዕኖ');

    $this->get('/en/innovation/challenges')->assertOk()->assertSee('Sample open data hackathon')->assertSee('Sample first prize');
    $this->get('/en/innovation/challenges/'.$slug)->assertOk()
        ->assertSeeInOrder(['Sample first prize', 'Sample second prize'])
        ->assertSeeInOrder(['Impact', '75%', 'Use of data', '25%'])
        ->assertSee('data-countdown', escape: false)
        ->assertSee('/en/innovation/challenges/'.$slug.'/register', escape: false)
        ->assertSee('"@type":"Event"', escape: false);
    $this->get('/am/innovation/challenges/'.$challenge->translation('am')->slug)->assertOk()->assertSee('ናሙና የክፍት መረጃ ሃካቶን');
});

it('checks the phase dates and the rubric when saving', function () {
    expect(fn () => sampleChallenge(['submission_closes_at' => CarbonImmutable::now()->toDateTimeString()]))->toThrow(ValidationException::class)
        ->and(fn () => sampleChallenge(['criteria' => []]))->toThrow(ValidationException::class);
});

it('registers a team once by name, within the size limit, and only while registration is open', function () {
    Mail::fake();
    $challenge = sampleChallenge();
    $slug = $challenge->translation('en')->slug;

    $this->get("/en/innovation/challenges/{$slug}/register")->assertOk()->assertSee(__('innovation.public.register_team', [], 'en'));
    $this->post("/en/innovation/challenges/{$slug}/register", teamForm(['members' => "A\nB\nC"]))->assertSessionHasErrors('members');
    $this->post("/en/innovation/challenges/{$slug}/register", teamForm(['consent' => null]))->assertSessionHasErrors('consent');

    $response = $this->post("/en/innovation/challenges/{$slug}/register", teamForm());
    $team = ChallengeTeam::query()->firstOrFail();
    $response->assertRedirect('/en/innovation/teams/'.$team->reference);
    expect($team->reference)->toMatch('/^'.ChallengeTeam::REFERENCE_PATTERN.'$/')
        ->and($team->members)->toBe("Sample Member One\nSample Member Two")
        ->and($team->getRawOriginal('contact_email'))->not->toContain('team@example.org');
    Mail::assertQueued(TeamRegisteredMail::class);

    $this->post("/en/innovation/challenges/{$slug}/register", teamForm(['contact_email' => 'other@example.org']))->assertSessionHasErrors('name');

    $this->travelTo(CarbonImmutable::now()->addDays(2));
    $this->post("/en/innovation/challenges/{$slug}/register", teamForm(['name' => 'Sample Late Team']))->assertSessionHasErrors('form');
    expect(ChallengeTeam::query()->count())->toBe(1);
});

it('lets a team send and change its submission until submissions close, with the reference and contact only', function () {
    Mail::fake();
    $challenge = sampleChallenge();
    $this->post('/en/innovation/challenges/'.$challenge->translation('en')->slug.'/register', teamForm());
    $team = ChallengeTeam::query()->firstOrFail();
    $submission = ['submission_title' => 'Sample Market Map', 'submission_description' => 'Sample: a map of market prices for farmers.', 'submission_links' => ['https://example.org/demo', '']];

    $this->post('/en/innovation/teams/'.$team->reference.'/submission', [...$submission, 'submission_links' => ['http://example.org']])->assertSessionHasErrors('submission_links.0');
    $this->post('/en/innovation/teams/'.$team->reference.'/submission', $submission)->assertRedirect();
    expect($team->refresh()->submitted_at)->not->toBeNull()->and($team->submission_links)->toBe(['https://example.org/demo']);

    $this->flushSession();
    $this->get('/en/innovation/teams/'.$team->reference)->assertOk()->assertDontSee('Sample Market Map');
    $this->post('/en/innovation/teams/'.$team->reference.'/submission', $submission)->assertForbidden();
    $this->post('/en/innovation/teams', ['reference' => $team->reference, 'contact' => 'nobody@example.org'])->assertSessionHasErrors('contact');
    $this->post('/en/innovation/teams', ['reference' => strtolower(str_replace('-', '', $team->reference)), 'contact' => '+251911223344'])->assertRedirect('/en/innovation/teams/'.$team->reference);
    $this->get('/en/innovation/teams/'.$team->reference)->assertSee('Sample Market Map');

    $this->travelTo(CarbonImmutable::now()->addDays(4));
    expect($challenge->refresh()->phase())->toBe(Challenge::PHASE_JUDGING);
    $this->post('/en/innovation/teams/'.$team->reference.'/submission', $submission)->assertSessionHasErrors('form');
    $this->get('/en/innovation/teams/'.$team->reference)->assertSee('Sample Market Map')->assertDontSee('name="submission_title"', escape: false);
});

it('scores by weighted rubric, honours conflicts, averages the judges and publishes ranked results', function () {
    Mail::fake();
    $challenge = sampleChallenge();
    $alpha = submitted($challenge, 'Sample Team Alpha', 'alpha@example.org');
    $hidden = submitted($challenge, 'Sample Hidden Team', 'hidden@example.org');
    app(ChallengeEntry::class)->register($challenge, ['name' => 'Sample Idle Team', 'contact_name' => 'Sample', 'contact_email' => 'idle@example.org', 'consent' => '1'], 'en');
    $first = staff(SystemRole::Judge);
    $second = staff(SystemRole::Judge);
    $judging = app(JudgeChallenge::class);

    expect(fn () => $judging->assign($challenge, [staff(SystemRole::Author)->id]))->toThrow(ValidationException::class);
    expect($judging->assign($challenge, [$first->id, $second->id]))->toBe(4)
        ->and($judging->assign($challenge, [$first->id]))->toBe(0);

    $mine = fn (int $judge, ChallengeTeam $team): JudgeAssignment => JudgeAssignment::query()->where('user_id', $judge)->where('challenge_team_id', $team->id)->firstOrFail();
    expect(fn () => $judging->score($mine($first->id, $alpha), $second, ['impact' => 5, 'use_of_data' => 5], null))->toThrow(ValidationException::class)
        ->and(fn () => $judging->score($mine($first->id, $alpha), $first, ['impact' => 11, 'use_of_data' => 5], null))->toThrow(ValidationException::class);

    $judging->score($mine($first->id, $alpha), $first, ['impact' => 8, 'use_of_data' => 4], 'Sample comment');
    expect($mine($first->id, $alpha)->total)->toBe('7.00')->and($alpha->refresh()->score)->toBe('7.00');
    $judging->score($mine($second->id, $alpha), $second, ['impact' => 10, 'use_of_data' => 6], null);
    expect($alpha->refresh()->score)->toBe('8.00');

    $judging->score($mine($first->id, $hidden), $first, ['impact' => 9, 'use_of_data' => 9], null);
    $judging->declareConflict($mine($second->id, $hidden), $second, 'Sample: I mentor this team.');
    expect($hidden->refresh()->score)->toBe('9.00')
        ->and(fn () => $judging->score($mine($second->id, $hidden), $second, ['impact' => 1, 'use_of_data' => 1], null))->toThrow(ValidationException::class);

    expect(fn () => app(SaveChallenge::class)->handle($challenge, [
        'translations' => ['en' => ['title' => 'Sample open data hackathon', 'published' => true]],
        'registration_opens_at' => $challenge->registration_opens_at->toDateTimeString(),
        'registration_closes_at' => $challenge->registration_closes_at->toDateTimeString(),
        'submission_closes_at' => $challenge->submission_closes_at->toDateTimeString(),
        'criteria' => [['labels' => ['en' => 'Only impact'], 'weight' => 1]],
    ], staff(SystemRole::InnovationOfficer)))->toThrow(ValidationException::class);

    $this->travelTo(CarbonImmutable::now()->addDays(4));
    expect($judging->publish($challenge))->toBe(2);
    expect($hidden->refresh()->rank)->toBe(1)->and($alpha->refresh()->rank)->toBe(2)
        ->and($challenge->refresh()->phase())->toBe(Challenge::PHASE_RESULTS)
        ->and(fn () => $judging->score($mine($first->id, $alpha), $first, ['impact' => 1, 'use_of_data' => 1], null))->toThrow(ValidationException::class);

    $this->get('/en/innovation/challenges/'.$challenge->translation('en')->slug)->assertOk()
        ->assertSeeInOrder([__('innovation.challenges.team_unnamed', [], 'en'), 'Sample Team Alpha'])
        ->assertDontSee('Sample Hidden Team')
        ->assertDontSee('Sample Idle Team')
        ->assertDontSee('data-countdown', escape: false);
});

it('keeps judges to their own assignments and staff screens to officers', function () {
    Mail::fake();
    $challenge = sampleChallenge();
    $team = submitted($challenge, 'Sample Team Alpha', 'alpha@example.org');
    $judge = staff(SystemRole::Judge);
    $other = staff(SystemRole::Judge);
    app(JudgeChallenge::class)->assign($challenge, [$judge->id, $other->id]);
    $own = JudgeAssignment::query()->where('user_id', $judge->id)->firstOrFail();
    $theirs = JudgeAssignment::query()->where('user_id', $other->id)->firstOrFail();

    $this->actingAs(staff(SystemRole::Author))->get('/admin/challenges')->assertForbidden();
    $this->actingAs(staff(SystemRole::Author))->get('/admin/my-judging')->assertForbidden();

    $this->actingAs($judge);
    $this->get('/admin/challenges')->assertForbidden();
    $this->get('/admin/challenge-teams')->assertForbidden();
    $this->get('/admin/my-judging')->assertOk()->assertSee('Sample Team Alpha app');
    $this->get('/admin/my-judging/'.$theirs->getRouteKey())->assertNotFound();
    $this->get('/admin/my-judging/'.$own->getRouteKey())->assertOk()->assertDontSee('alpha@example.org');
    livewire(ViewJudgeAssignment::class, ['record' => $own->getRouteKey()])
        ->callAction('score', data: ['scores' => ['impact' => '6', 'use_of_data' => '2'], 'comment' => null])
        ->assertHasNoActionErrors();
    expect($own->refresh()->total)->toBe('5.00')->and($team->refresh()->score)->toBe('5.00');

    $this->actingAs(staff(SystemRole::InnovationOfficer));
    $this->get('/admin/challenge-teams')->assertOk()->assertSee('Sample Team Alpha');
    livewire(CreateChallenge::class)
        ->fillForm([
            'translations' => ['en' => ['title' => 'Sample admin challenge', 'published' => true]],
            'registration_opens_at' => CarbonImmutable::now()->addDay()->toDateTimeString(),
            'registration_closes_at' => CarbonImmutable::now()->addDays(5)->toDateTimeString(),
            'submission_closes_at' => CarbonImmutable::now()->addDays(9)->toDateTimeString(),
            'max_team_size' => 4,
            'criteria' => [['labels' => ['en' => 'Sample criterion'], 'weight' => 2]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();
    expect(Challenge::query()->count())->toBe(2);
});

it('finds, exports and erases a team contact, and the purge clears old teams', function () {
    Mail::fake();
    $challenge = sampleChallenge();
    $team = submitted($challenge, 'Sample Team Alpha', 'alpha@example.org');
    $search = new PersonalDataSearch('alpha@example.org', null);

    expect($search->counts())->toMatchArray(['teams' => 1]);
    app(ErasePersonalData::class)->handle($search, staff(SystemRole::Admin));
    expect($team->refresh()->contact_email)->toBeNull()
        ->and($team->erased_at)->not->toBeNull()
        ->and($team->show_in_results)->toBeFalse()
        ->and($team->submission_title)->toBe('Sample Team Alpha app')
        ->and((new PersonalDataSearch('alpha@example.org', null))->counts())->toMatchArray(['teams' => 0]);

    $old = submitted($challenge, 'Sample Team Beta', 'beta@example.org');
    $this->travelTo(CarbonImmutable::now()->addDays(800));
    $this->artisan('innovation:purge')->assertSuccessful();
    expect($old->refresh()->contact_email)->toBeNull()->and($old->members)->toBeNull()->and($old->name)->toBe('Sample Team Beta');
});

it('puts the nearest open call on the homepage with a countdown, and the next one when none is open', function () {
    $officer = staff(SystemRole::InnovationOfficer);
    $later = sampleChallenge([
        'translations' => ['en' => ['title' => 'Sample later challenge', 'published' => true]],
        'registration_opens_at' => CarbonImmutable::now()->addWeek()->toDateTimeString(),
        'registration_closes_at' => CarbonImmutable::now()->addWeeks(2)->toDateTimeString(),
        'submission_closes_at' => CarbonImmutable::now()->addWeeks(3)->toDateTimeString(),
    ]);

    expect(OpenCalls::for('en'))->toHaveCount(1)
        ->and(OpenCalls::for('en')[0])->toMatchArray(['kind' => 'challenge', 'title' => 'Sample later challenge', 'open' => false]);

    sampleChallenge();
    app(SaveProgramme::class)->handle(new Programme, [
        'kind' => 'incubation',
        'translations' => ['en' => ['title' => 'Sample incubation call', 'published' => true]],
        'cohorts' => [['name' => 'Sample cohort', 'opens_at' => CarbonImmutable::now()->subDay()->toDateTimeString(), 'closes_at' => CarbonImmutable::now()->addHours(12)->toDateTimeString()]],
    ], $officer);

    $calls = OpenCalls::for('en');
    expect(array_column($calls, 'title'))->toBe(['Sample incubation call', 'Sample open data hackathon'])
        ->and(array_column($calls, 'open'))->toBe([true, true]);

    $this->get('/en')->assertOk()
        ->assertSee(__('homepage.section.innovation', [], 'en'))
        ->assertSeeInOrder(['Sample incubation call', 'data-countdown', 'Sample open data hackathon'], escape: false)
        ->assertDontSee('Sample later challenge')
        ->assertSee(__('homepage.live.challenges_open', [], 'en'));
    expect($later->phase())->toBe(Challenge::PHASE_UPCOMING);
});

it('adds labelled innovation samples once and removes exactly them', function () {
    Mail::fake();
    staff(SystemRole::SuperAdmin);
    $this->artisan('portal:sample-innovation')->assertSuccessful();
    $this->artisan('portal:sample-innovation')->assertSuccessful();

    expect(Challenge::query()->count())->toBe(1)->and(Programme::query()->count())->toBe(1)->and(Startup::query()->listed()->count())->toBe(1)
        ->and(OpenCalls::for('en'))->toHaveCount(2);
    $this->get('/en/innovation/startups')->assertSee('[Sample] Harar Market Link');
    Mail::assertNothingQueued();

    $this->artisan('portal:sample-innovation --remove')->assertSuccessful();
    expect(Challenge::withTrashed()->count())->toBe(0)->and(Programme::withTrashed()->count())->toBe(0)->and(Startup::withTrashed()->count())->toBe(0);
});

it('publishes results once, after submissions close, and takes no submission after that (ADR-0054)', function () {
    Mail::fake();
    $challenge = sampleChallenge();
    $team = submitted($challenge, 'Sample Team Early', 'early@example.org');
    $team->forceFill(['score' => '7.00'])->save();
    $judging = app(JudgeChallenge::class);

    // Still building: publishing early would leave submissions open, so it is refused.
    expect(fn () => $judging->publish($challenge))->toThrow(ValidationException::class)
        ->and($challenge->refresh()->results_published_at)->toBeNull();

    $this->travelTo(CarbonImmutable::now()->addDays(4));
    expect($judging->publish($challenge))->toBe(1);
    expect(fn () => $judging->publish($challenge->refresh()))->toThrow(ValidationException::class);

    // Even with dates moved back, published results close the challenge to new work.
    $challenge->forceFill(['submission_closes_at' => CarbonImmutable::now()->addDays(5)])->save();
    expect($challenge->refresh()->takesSubmissions())->toBeFalse()
        ->and(fn () => app(ChallengeEntry::class)->submit($team->refresh(), ['submission_title' => 'Sample late', 'submission_description' => 'Sample: sent after the results were out.']))
        ->toThrow(ValidationException::class);
});
