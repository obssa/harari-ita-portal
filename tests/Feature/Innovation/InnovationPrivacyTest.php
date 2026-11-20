<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Innovation\Actions\ReviewApplication;
use App\Domain\Innovation\Actions\ReviewStartup;
use App\Domain\Innovation\Actions\SaveProgramme;
use App\Domain\Innovation\Actions\SubmitApplication;
use App\Domain\Innovation\Actions\SubmitStartup;
use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Enums\ListingStatus;
use App\Domain\Innovation\Models\Application;
use App\Domain\Innovation\Models\Programme;
use App\Domain\Innovation\Models\Startup;
use App\Domain\Privacy\Actions\ErasePersonalData;
use App\Domain\Privacy\Actions\ExportPersonalData;
use App\Domain\Privacy\Support\PersonalDataSearch;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;

function privacyProgramme(): Programme
{
    return app(SaveProgramme::class)->handle(new Programme, [
        'kind' => 'incubation',
        'translations' => ['en' => ['title' => 'Sample privacy programme', 'published' => true]],
        'cohorts' => [['name' => 'Sample cohort', 'opens_at' => CarbonImmutable::now()->subDay()->toDateTimeString(), 'closes_at' => CarbonImmutable::now()->addDay()->toDateTimeString()]],
    ], staff(SystemRole::InnovationOfficer));
}

it('finds, exports and erases a person\'s applications and listing requests', function () {
    Mail::fake();
    $application = app(SubmitApplication::class)->handle(privacyProgramme(), ['applicant_name' => 'Sample Person', 'applicant_email' => 'person@example.org', 'consent' => true], 'en');
    app(SubmitStartup::class)->handle(['name' => 'Sample Startup', 'summary' => 'Sample: a startup described in one sentence here.', 'stage' => 'idea', 'founder_name' => 'Sample Person', 'founder_email' => 'person@example.org', 'consent' => true], 'en');

    $search = new PersonalDataSearch('person@example.org', null);
    expect($search->counts())->toMatchArray(['applications' => 1, 'startups' => 1]);

    $export = app(ExportPersonalData::class)->handle($search, staff(SystemRole::Admin));
    expect($export['programme_applications'][0]['reference'])->toBe($application->reference)
        ->and($export['startup_listings'][0]['founder_email'])->toBe('person@example.org');

    app(ErasePersonalData::class)->handle(new PersonalDataSearch('person@example.org', null), staff(SystemRole::Admin));
    expect($application->refresh()->applicant_email)->toBeNull()
        ->and($application->answers)->toBeNull()
        ->and(Startup::query()->firstOrFail()->founder_email)->toBeNull()
        ->and((new PersonalDataSearch('person@example.org', null))->counts())->toMatchArray(['applications' => 0, 'startups' => 0]);
});

it('deletes decided applications and declined listing requests after the retention period', function () {
    Mail::fake();
    $officer = staff(SystemRole::InnovationOfficer);
    $decided = app(SubmitApplication::class)->handle(privacyProgramme(), ['applicant_name' => 'Sample One', 'applicant_email' => 'one@example.org', 'consent' => true], 'en');
    app(ReviewApplication::class)->move($decided, ApplicationStage::Declined, 'Sample note', $officer);
    $open = app(SubmitApplication::class)->handle(Programme::query()->firstOrFail(), ['applicant_name' => 'Sample Two', 'applicant_email' => 'two@example.org', 'consent' => true], 'en');
    $startup = app(SubmitStartup::class)->handle(['name' => 'Sample Declined', 'summary' => 'Sample: a startup described in one sentence here.', 'stage' => 'idea', 'founder_name' => 'Sample', 'founder_email' => 'three@example.org', 'consent' => true], 'en');
    $startup->forceFill(['listing_status' => ListingStatus::Declined])->save();

    $this->travelTo(CarbonImmutable::now()->addDays((int) config('portal.innovation.retention_days') + 1));
    $this->artisan('innovation:purge')->assertSuccessful();

    expect(Application::query()->whereKey($decided->id)->exists())->toBeFalse()
        ->and(Application::query()->whereKey($open->id)->exists())->toBeTrue()
        ->and(Startup::query()->withTrashed()->whereKey($startup->id)->exists())->toBeFalse();
});

it('counts the retention period from the decision, so a later edit does not keep the record longer (ADR-0054)', function () {
    Mail::fake();
    $officer = staff(SystemRole::InnovationOfficer);
    $days = (int) config('portal.innovation.retention_days');
    $declined = app(SubmitApplication::class)->handle(privacyProgramme(), ['applicant_name' => 'Sample One', 'applicant_email' => 'one@example.org', 'consent' => true], 'en');
    app(ReviewApplication::class)->move($declined, ApplicationStage::Declined, 'Sample note', $officer);
    $withdrawn = app(SubmitApplication::class)->handle(Programme::query()->firstOrFail(), ['applicant_name' => 'Sample Two', 'applicant_email' => 'two@example.org', 'consent' => true], 'en');
    app(ReviewApplication::class)->withdraw($withdrawn);
    $startup = app(SubmitStartup::class)->handle(['name' => 'Sample Declined', 'summary' => 'Sample: a startup described in one sentence here.', 'stage' => 'idea', 'founder_name' => 'Sample', 'founder_email' => 'three@example.org', 'consent' => true], 'en');
    app(ReviewStartup::class)->decide($startup, ListingStatus::Declined, 'Sample: not a startup.', $officer);
    expect($withdrawn->decided_at)->not->toBeNull()->and($startup->decided_at)->not->toBeNull();

    // Edited a week before the period ends: still deleted when the period counted from the decision is over.
    $this->travelTo(CarbonImmutable::now()->addDays($days - 7));
    foreach ([$declined, $withdrawn, $startup] as $record) {
        $record->refresh()->touch();
    }
    $this->travelTo(CarbonImmutable::now()->addDays(8));
    $this->artisan('innovation:purge')->assertSuccessful();

    expect(Application::query()->whereKey($declined->id)->exists())->toBeFalse()
        ->and(Application::query()->whereKey($withdrawn->id)->exists())->toBeFalse()
        ->and(Startup::query()->withTrashed()->whereKey($startup->id)->exists())->toBeFalse();
});
