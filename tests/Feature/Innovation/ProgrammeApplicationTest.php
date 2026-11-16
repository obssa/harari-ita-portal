<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Innovation\Actions\ReviewApplication;
use App\Domain\Innovation\Actions\SaveProgramme;
use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Mail\ApplicationMail;
use App\Domain\Innovation\Models\Application;
use App\Domain\Innovation\Models\Programme;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Filament\Resources\Programmes\Pages\CreateProgramme;
use App\Support\SpamGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

/**
 * @param  array<string, mixed>  $data
 */
function sampleProgramme(array $data = []): Programme
{
    return app(SaveProgramme::class)->handle(new Programme, [
        'kind' => 'incubation',
        'translations' => [
            'en' => ['title' => 'Sample incubation programme', 'summary' => 'Sample: six months of support.', 'benefits' => "Sample mentoring\nSample workspace", 'timeline' => "Month 1: sample training\nMonth 6: sample demo day", 'published' => true],
            'am' => ['title' => 'ናሙና የማቀፊያ ፕሮግራም', 'published' => true],
        ],
        'questions' => [
            ['labels' => ['en' => 'What problem do you solve?', 'am' => 'የትኛውን ችግር ይፈታሉ?'], 'long' => true, 'required' => true],
            ['labels' => ['en' => 'Your website'], 'long' => false, 'required' => false],
        ],
        'cohorts' => [[
            'name' => 'Sample cohort 1',
            'opens_at' => CarbonImmutable::now()->subDay()->toDateTimeString(),
            'closes_at' => CarbonImmutable::now()->addWeek()->toDateTimeString(),
            'places' => 10,
        ]],
        ...$data,
    ], staff(SystemRole::InnovationOfficer));
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function applicationForm(Programme $programme, array $overrides = []): array
{
    $keys = array_column($programme->questionsIn('en'), 'key');

    return [
        'applicant_name' => 'Sample Applicant',
        'applicant_email' => 'applicant@example.org',
        'applicant_phone' => '0911443322',
        'team_name' => 'Sample team',
        'answers' => [$keys[0] => 'Sample: farmers cannot sell online.'],
        'consent' => '1',
        SpamGuard::STARTED => Crypt::encryptString((string) (time() - 10)),
        ...$overrides,
    ];
}

it('shows a programme with its benefits, timeline and open cohort in its languages', function () {
    $programme = sampleProgramme();
    $slug = $programme->translation('en')->slug;

    expect($programme->questionsIn('en'))->toHaveCount(2)
        ->and($programme->questionsIn('am')[0]['label'])->toBe('የትኛውን ችግር ይፈታሉ?')
        ->and($programme->openCohort()?->name)->toBe('Sample cohort 1');

    $this->get('/en/innovation/programmes')->assertOk()->assertSee('Sample incubation programme')->assertSee(__('innovation.programmes.open', [], 'en'));
    $this->get('/en/innovation/programmes/'.$slug)->assertOk()
        ->assertSeeInOrder(['Sample mentoring', 'Sample workspace', 'Month 1: sample training'])
        ->assertSee('/en/innovation/programmes/'.$slug.'/apply', escape: false)
        ->assertSee('"@type":"EducationalOccupationalProgram"', escape: false);
    $this->get('/am/innovation/programmes/'.$programme->translation('am')->slug)->assertOk()->assertSee('ናሙና የማቀፊያ ፕሮግራም');
});

it('takes an application to the open cohort, once per email, with the required answers', function () {
    Mail::fake();
    $programme = sampleProgramme();
    $slug = $programme->translation('en')->slug;

    $this->get("/en/innovation/programmes/{$slug}/apply")->assertOk()->assertSee('What problem do you solve?');
    $this->post("/en/innovation/programmes/{$slug}/apply", applicationForm($programme, ['answers' => []]))->assertSessionHasErrors();

    $response = $this->post("/en/innovation/programmes/{$slug}/apply", applicationForm($programme));
    $application = Application::query()->firstOrFail();
    $response->assertRedirect('/en/innovation/applications/'.$application->reference);
    expect($application->stage)->toBe(ApplicationStage::Received)
        ->and($application->answers)->toHaveCount(1)
        ->and($application->reference)->toMatch('/^'.Application::REFERENCE_PATTERN.'$/');
    Mail::assertQueued(ApplicationMail::class, fn (ApplicationMail $mail): bool => $mail->kind === 'received');

    $this->post("/en/innovation/programmes/{$slug}/apply", applicationForm($programme))->assertSessionHasErrors('applicant_email');
    expect(Application::query()->count())->toBe(1);
});

it('refuses applications when no cohort is open', function () {
    $programme = sampleProgramme(['cohorts' => [[
        'name' => 'Sample later cohort',
        'opens_at' => CarbonImmutable::now()->addMonth()->toDateTimeString(),
        'closes_at' => CarbonImmutable::now()->addMonths(2)->toDateTimeString(),
    ]]]);
    $slug = $programme->translation('en')->slug;

    $this->get('/en/innovation/programmes/'.$slug)->assertSee(__('innovation.programmes.next', ['name' => 'Sample later cohort'], 'en'));
    $this->post("/en/innovation/programmes/{$slug}/apply", applicationForm($programme))->assertSessionHasErrors('form');
});

it('takes an application through screening, scoring, interview and a decision the applicant reads', function () {
    Mail::fake();
    $programme = sampleProgramme();
    $this->post('/en/innovation/programmes/'.$programme->translation('en')->slug.'/apply', applicationForm($programme));
    $application = Application::query()->firstOrFail();
    $officer = staff(SystemRole::InnovationOfficer);
    $review = app(ReviewApplication::class);

    $review->score($application, ['problem' => 5, 'solution' => 4, 'team' => 4, 'impact' => 5, 'feasibility' => 3], $officer);
    expect($application->refresh()->score)->toBe('4.20')->and($application->stage)->toBe(ApplicationStage::Screening);
    expect(fn () => $review->score($application, ['problem' => 9], $officer))->toThrow(ValidationException::class);

    $review->move($application, ApplicationStage::Interview, null, $officer);
    expect(fn () => $review->move($application, ApplicationStage::Accepted, '', $officer))->toThrow(ValidationException::class);
    $review->move($application, ApplicationStage::Accepted, 'Sample: welcome to cohort 1.', $officer);
    expect($application->refresh()->stage)->toBe(ApplicationStage::Accepted)->and($application->decided_at)->not->toBeNull();
    Mail::assertQueued(ApplicationMail::class, fn (ApplicationMail $mail): bool => $mail->kind === 'decided');

    $this->get('/en/innovation/applications/'.$application->reference)->assertSee('Sample: welcome to cohort 1.')
        ->assertDontSee(__('innovation.public.withdraw', [], 'en'));
});

it('lets applicants follow and withdraw with the reference and their contact only', function () {
    Mail::fake();
    $programme = sampleProgramme();
    $this->post('/en/innovation/programmes/'.$programme->translation('en')->slug.'/apply', applicationForm($programme));
    $application = Application::query()->firstOrFail();
    $this->flushSession();

    $this->get('/en/innovation/applications/'.$application->reference)->assertOk()->assertDontSee('Sample incubation programme');
    $this->post('/en/innovation/applications/'.$application->reference.'/withdraw')->assertForbidden();
    $this->post('/en/innovation/applications', ['reference' => $application->reference, 'contact' => 'nobody@example.org'])->assertSessionHasErrors('contact');
    $this->post('/en/innovation/applications', ['reference' => $application->reference, 'contact' => 'applicant@example.org'])->assertRedirect();

    $this->post('/en/innovation/applications/'.$application->reference.'/withdraw')->assertRedirect();
    expect($application->refresh()->stage)->toBe(ApplicationStage::Withdrawn);
});

it('lets officers build programmes and review applications in the admin, and keeps others out', function () {
    Mail::fake();
    $this->actingAs(staff(SystemRole::Author))->get('/admin/programmes')->assertForbidden();
    $this->actingAs(staff(SystemRole::Author))->get('/admin/applications')->assertForbidden();

    $this->actingAs(staff(SystemRole::InnovationOfficer));
    livewire(CreateProgramme::class)
        ->fillForm(['kind' => 'acceleration', 'translations' => ['en' => ['title' => 'Sample accelerator', 'published' => true]]])
        ->call('create')
        ->assertHasNoFormErrors();
    expect(Programme::query()->count())->toBe(1);

    $programme = sampleProgramme();
    $this->post('/en/innovation/programmes/'.$programme->translation('en')->slug.'/apply', applicationForm($programme));
    $application = Application::query()->firstOrFail();

    $this->get('/admin/applications')->assertOk()->assertSee($application->reference);
    livewire(ViewApplication::class, ['record' => $application->getRouteKey()])
        ->assertSee('Sample: farmers cannot sell online.')
        ->callAction('move', data: ['stage' => 'interview', 'note' => null])
        ->assertHasNoActionErrors();
    expect($application->refresh()->stage)->toBe(ApplicationStage::Interview);
});
