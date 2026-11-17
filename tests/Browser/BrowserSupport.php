<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Content\Actions\SavePost;
use App\Domain\Content\Models\Post;
use App\Domain\Content\Workflow\ContentWorkflow;
use App\Domain\Content\Workflow\Transition;
use App\Domain\Events\Actions\SaveEvent;
use App\Domain\Events\Models\Event;
use App\Domain\Forms\Actions\SaveForm;
use App\Domain\Forms\Models\Form;
use App\Domain\Forms\Support\FormField;
use App\Domain\Innovation\Actions\SaveChallenge;
use App\Domain\Innovation\Models\Challenge;
use App\Domain\Media\Actions\UploadMedia;
use App\Domain\OpenData\Actions\SaveDataset;
use App\Domain\OpenData\Actions\SaveDistribution;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\OpenData\Models\DatasetDistribution;
use App\Domain\Organisation\Actions\SaveOffice;
use App\Domain\Organisation\Models\Office;
use App\Domain\Projects\Actions\RecordKpiReading;
use App\Domain\Projects\Actions\SaveKpi;
use App\Domain\Projects\Actions\SaveProject;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectKpi;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\TestFiles;

/*
| Sample content for the browser tests (PLAN.md §13: no real people): one
| published story, an event, an office and the contact form.
*/

function browserContent(): void
{
    $publisher = staff(SystemRole::CommunicationsEditor, mfa: false);

    $post = app(SavePost::class)->handle(new Post, ['type' => 'news', 'translations' => [
        'en' => ['title' => 'Sample browser story', 'summary' => 'A sample story for the browser tests.'],
        'am' => ['title' => 'የናሙና የአሳሽ ዜና', 'summary' => 'ለአሳሽ ሙከራዎች የናሙና ዜና።'],
    ]], $publisher);
    foreach (['en', 'am'] as $locale) {
        app(ContentWorkflow::class)->apply($post, $locale, Transition::Publish, $publisher);
    }

    app(SaveEvent::class)->handle(new Event, [
        'starts_at' => CarbonImmutable::now()->addDays(5)->setTime(9, 0)->toDateTimeString(),
        'registration_enabled' => true,
        'translations' => ['en' => ['title' => 'Sample browser forum', 'venue' => 'Sample hall', 'published' => true]],
    ], $publisher);

    // ADR-0022: a challenge taking teams, so the challenge page, its countdown and the register form are checked.
    app(SaveChallenge::class)->handle(new Challenge, [
        'translations' => ['en' => ['title' => 'Sample browser hackathon', 'summary' => 'A sample challenge for the browser tests.', 'prizes' => 'Sample first prize
Sample second prize', 'rules' => 'Sample rule.', 'published' => true]],
        'registration_opens_at' => CarbonImmutable::now()->subDay()->toDateTimeString(),
        'registration_closes_at' => CarbonImmutable::now()->addWeek()->toDateTimeString(),
        'submission_closes_at' => CarbonImmutable::now()->addWeeks(2)->toDateTimeString(),
        'criteria' => [['labels' => ['en' => 'Sample impact'], 'weight' => 2], ['labels' => ['en' => 'Sample design'], 'weight' => 1]],
    ], staff(SystemRole::InnovationOfficer, mfa: false));

    // ADR-0024..0027: a running project with milestones and readings, and a dataset with a CSV file.
    $manager = staff(SystemRole::ProjectManager, mfa: false);
    $project = app(SaveProject::class)->handle(new Project, [
        'status' => 'ongoing', 'rag_status' => 'amber', 'latitude' => '9.31', 'longitude' => '42.12',
        'milestones' => [['titles' => ['en' => 'Sample survey'], 'due_on' => '2026-01-10', 'done_on' => '2026-01-08'], ['titles' => ['en' => 'Sample build'], 'due_on' => '2026-02-01']],
        'translations' => ['en' => ['title' => 'Sample browser project', 'summary' => 'A sample project for the browser tests.', 'published' => true]],
    ], $manager);
    $kpi = app(SaveKpi::class)->handle($project, new ProjectKpi, ['names' => ['en' => 'Sample sites online'], 'baseline' => 0, 'target' => 10], $manager);
    foreach (['2026-01-01' => 2, '2026-02-01' => 5] as $date => $value) {
        app(RecordKpiReading::class)->handle($kpi, ['value' => $value, 'as_of' => $date], $manager);
    }
    $steward = staff(SystemRole::DataSteward, mfa: false);
    $dataset = app(SaveDataset::class)->handle(new Dataset, ['frequency' => 'monthly', 'translations' => ['en' => ['title' => 'Sample browser data', 'summary' => 'A sample dataset for the browser tests.', 'published' => true]]], $steward);
    $csv = app(UploadMedia::class)->handle(TestFiles::text("month,count\n2026-01,4\n2026-02,7\n", 'csv'), 'sample-browser.csv');
    app(SaveDistribution::class)->handle($dataset, new DatasetDistribution, ['format' => 'csv', 'media_id' => $csv->id], $steward);

    // ADR-0028..0031: the labelled Academy samples: a course, a cohort taking enrolments and a certificate.
    staff(SystemRole::SuperAdmin, mfa: false);
    Artisan::call('portal:sample-academy');
    Artisan::call('portal:sample-opportunities');

    app(SaveOffice::class)->handle(new Office, ['type' => 'regional_office', 'email' => 'office@example.org', 'phone' => '+251911000000',
        'translations' => ['en' => ['name' => 'Sample browser office', 'published' => true]]], $publisher);

    app(SaveForm::class)->handle(new Form, [
        'type' => 'contact',
        'on_contact_page' => true,
        'active' => true,
        'response_days' => 3,
        'translations' => ['en' => ['title' => 'Sample contact form', 'success_message' => 'Sample thanks for writing.', 'published' => true]],
        'fields' => [['key' => 'msgfield', 'type' => FormField::TEXTAREA, 'required' => true, 'label' => ['en' => 'Your message']]],
    ], $publisher);
}
