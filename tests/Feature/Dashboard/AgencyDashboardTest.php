<?php

use App\Domain\Access\Enums\ScopeType;
use App\Domain\Access\Enums\SystemRole;
use App\Domain\Innovation\Actions\SaveProgramme;
use App\Domain\Innovation\Models\Application;
use App\Domain\Innovation\Models\Programme;
use App\Domain\Notifications\Notifications\WeeklyDigestNotice;
use App\Domain\Opportunities\Actions\SaveOpportunity;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Organisation\Actions\SaveOffice;
use App\Domain\Organisation\Models\Office;
use App\Domain\Projects\Actions\SaveProject;
use App\Domain\Projects\Models\Project;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Applications\Pages\ApplicationBoard;
use App\Filament\Resources\CourseCohorts\Pages\EditCourseCohort;
use App\Filament\Resources\CourseCohorts\RelationManagers\EnrolmentsRelationManager;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Datasets\DatasetResource;
use App\Filament\Resources\Opportunities\OpportunityResource;
use App\Filament\Resources\Opportunities\Pages\ListOpportunities;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Tickets\TicketResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\DashboardRegistry;
use App\Filament\Widgets\AcademyCohorts;
use App\Filament\Widgets\AtAGlance;
use App\Filament\Widgets\InnovationPipeline;
use App\Filament\Widgets\OpportunityDeadlines;
use App\Filament\Widgets\ProjectsAndData;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;

use function Pest\Livewire\livewire;

function agencyOffice(string $name): Office
{
    return app(SaveOffice::class)->handle(new Office, ['type' => 'directorate', 'translations' => ['en' => ['name' => $name, 'published' => true]]], staff(SystemRole::CommunicationsEditor));
}

/** A directorate head limited to one directorate. */
function headOf(Office $office): User
{
    $user = User::factory()->create();
    $user->assignScopedRole(SystemRole::DirectorateHead, ScopeType::Office, $office->id);
    if (SystemRole::DirectorateHead->requiresMfa()) {
        $user->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP'])->saveQuietly();
    }

    return $user->fresh() ?? $user;
}

/**
 * @param  array<string, mixed>  $data
 */
function agencyOpportunity(string $title, array $data = []): Opportunity
{
    return app(SaveOpportunity::class)->handle(new Opportunity, [
        'type' => 'tender', 'closes_at' => CarbonImmutable::now()->addDays(3)->toDateTimeString(),
        'translations' => ['en' => ['title' => $title, 'published' => true]], ...$data,
    ], staff(SystemRole::ProcurementOfficer));
}

function agencyProject(string $title, Office $office, string $rag): Project
{
    return app(SaveProject::class)->handle(new Project, [
        'translations' => ['en' => ['title' => $title, 'published' => true]], 'status' => 'ongoing', 'office_id' => $office->id, 'rag_status' => $rag, 'rag_reason' => 'Sample reason.',
    ], staff(SystemRole::Admin));
}

it('shows the Agency widgets with live records, scoped to the directorates a person covers', function () {
    $ict = agencyOffice('Sample ICT directorate');
    $other = agencyOffice('Sample other directorate');
    agencyProject('Sample red project', $ict, 'red');
    agencyProject('Sample hidden amber project', $other, 'amber');
    agencyOpportunity('Sample closing tender', ['office_id' => $ict->id]);
    agencyOpportunity('Sample other tender', ['office_id' => $other->id]);
    $cohort = sampleCohort(sampleCourse(), ['capacity' => 12]);

    $head = headOf($ict);
    $this->actingAs($head);
    livewire(ProjectsAndData::class)->assertOk()->assertSee('Sample red project')->assertDontSee('Sample hidden amber project');
    livewire(OpportunityDeadlines::class)->assertOk()->assertSee('Sample closing tender')->assertDontSee('Sample other tender');
    livewire(AtAGlance::class)->assertOk()->assertSee(__('dashboard.glance.opportunities_open'));

    $this->actingAs(staff(SystemRole::Admin));
    livewire(ProjectsAndData::class)->assertSee('Sample red project')->assertSee('Sample hidden amber project');
    livewire(OpportunityDeadlines::class, ['pageFilters' => ['office' => $other->id]])->assertSee('Sample other tender')->assertDontSee('Sample closing tender');
    livewire(AcademyCohorts::class)->assertOk()->assertSee('Sample cohort')->assertSee(__('dashboard.academy.places', ['taken' => 0, 'total' => 12]));

    // The academy widget follows the instructor scope: an instructor without cohorts sees none.
    $this->actingAs(staff(SystemRole::Instructor, mfa: false));
    livewire(AcademyCohorts::class)->assertOk()->assertDontSee('Sample cohort');
    expect($cohort->exists)->toBeTrue();
});

it('shows the innovation pipeline by stage and the applications board', function () {
    $programme = app(SaveProgramme::class)->handle(new Programme, [
        'kind' => 'incubation', 'translations' => ['en' => ['title' => 'Sample pipeline programme', 'published' => true]],
        'questions' => [['labels' => ['en' => 'Sample question?'], 'long' => false, 'required' => false]],
        'cohorts' => [['name' => 'Sample cohort A', 'opens_at' => now()->subDay()->toDateTimeString(), 'closes_at' => now()->addWeek()->toDateTimeString(), 'places' => 5]],
    ], staff(SystemRole::InnovationOfficer));
    $cohort = $programme->cohorts()->firstOrFail();
    foreach (['received', 'received', 'screening'] as $i => $stage) {
        (new Application)->forceFill([
            'reference' => Application::newReference(), 'programme_id' => $programme->id, 'cohort_id' => $cohort->id, 'locale' => 'en',
            'applicant_name' => 'Sample Applicant '.$i, 'team_name' => 'Sample team '.$i, 'answers' => [], 'stage' => $stage, 'consented_at' => now(),
        ])->save();
    }

    $officer = staff(SystemRole::InnovationOfficer);
    $this->actingAs($officer);
    livewire(InnovationPipeline::class)->assertOk()->assertSee('Sample pipeline programme')
        ->assertSee(__('innovation.application_stage.received'))->assertDontSee('Sample Applicant 0');
    livewire(ApplicationBoard::class)->assertOk()->assertSee('Sample team 0')->assertSee('Sample team 2')->set('cohort', $cohort->id)->assertSee('Sample team 1');
    $this->get(ApplicationResource::getUrl('board'))->assertOk();
    $this->actingAs(staff(SystemRole::ProcurementOfficer))->get(ApplicationResource::getUrl('board'))->assertForbidden();
});

it('exports lists as CSV for those allowed, formula-safe, and logs each export without the data', function () {
    agencyOpportunity('=HYPERLINK("sample")');
    $officer = staff(SystemRole::ProcurementOfficer);
    $this->actingAs($officer);
    $response = livewire(ListOpportunities::class)->callAction('export');
    $response->assertFileDownloaded();
    $csv = $response->effects['download']['content'] ?? '';
    expect(base64_decode((string) $csv))->toContain("\"'=HYPERLINK(\"\"sample\"\")\"")
        ->and(Activity::query()->where('event', 'exported')->where('log_name', 'opportunities')->count())->toBe(1);

    // The learners of a cohort: personal data, only for those who manage cohorts.
    $cohort = sampleCohort(sampleCourse());
    $coordinator = staff(SystemRole::AcademyCoordinator);
    $this->actingAs($coordinator);
    livewire(EnrolmentsRelationManager::class, ['ownerRecord' => $cohort, 'pageClass' => EditCourseCohort::class])
        ->assertTableActionVisible('export')->callTableAction('export')->assertFileDownloaded();
    $this->actingAs(staff(SystemRole::Instructor, mfa: false));
    livewire(EnrolmentsRelationManager::class, ['ownerRecord' => $cohort, 'pageClass' => EditCourseCohort::class])->assertTableActionHidden('export');
});

it('sends the weekly digest to directorate heads and Admins, each for what they cover, and only when there is news', function () {
    Notification::fake();
    $ict = agencyOffice('Sample ICT directorate');
    $other = agencyOffice('Sample other directorate');
    agencyProject('Sample red project', $ict, 'red');
    agencyProject('Sample hidden amber project', $other, 'amber');
    $head = headOf($ict);
    $admin = staff(SystemRole::Admin);
    $editor = staff(SystemRole::Editor);
    $quiet = staff(SystemRole::Admin);
    $quiet->forceFill(['notification_preferences' => ['digest' => []]])->save();

    $this->artisan('reports:weekly-digest')->assertSuccessful();

    Notification::assertSentTo($head, WeeklyDigestNotice::class, function (WeeklyDigestNotice $notice) use ($head): bool {
        $mail = implode("\n", $notice->toMail($head)->introLines);

        return str_contains($mail, 'Sample red project') && ! str_contains($mail, 'Sample hidden amber project') && str_contains($mail, 'Sample ICT directorate');
    });
    Notification::assertSentTo($admin, WeeklyDigestNotice::class, fn (WeeklyDigestNotice $notice): bool => str_contains(implode("\n", $notice->toMail($admin)->introLines), 'Sample hidden amber project'));
    Notification::assertNotSentTo($editor, WeeklyDigestNotice::class);
    Notification::assertNotSentTo($quiet, WeeklyDigestNotice::class);

    // A directorate with nothing to report receives nothing.
    $empty = headOf(agencyOffice('Sample quiet directorate'));
    Notification::fake();
    $this->artisan('reports:weekly-digest', ['--user' => $empty->id])->assertSuccessful();
    Notification::assertNothingSent();
});

it('lets each role reach only its own admin screens and gives it a matching dashboard', function (SystemRole $role, array $allowed) {
    $screens = [
        'opportunities' => OpportunityResource::class, 'datasets' => DatasetResource::class, 'courses' => CourseResource::class,
        'applications' => ApplicationResource::class, 'tickets' => TicketResource::class, 'projects' => ProjectResource::class,
        'posts' => PostResource::class, 'users' => UserResource::class,
    ];
    $this->actingAs(staff($role));
    foreach ($screens as $name => $resource) {
        $status = $this->get($resource::getUrl())->getStatusCode();
        expect([$name => $status])->toBe([$name => in_array($name, $allowed, true) ? 200 : 403]);
    }

    $keys = array_column(DashboardRegistry::layoutFor(staff($role)), 'key');
    expect(in_array('opportunity_deadlines', $keys, true))->toBe(in_array('opportunities', $allowed, true))
        ->and(in_array('innovation_pipeline', $keys, true))->toBe(in_array('applications', $allowed, true))
        ->and(in_array('projects_data', $keys, true))->toBe(in_array('projects', $allowed, true));
})->with([
    'procurement officer' => [SystemRole::ProcurementOfficer, ['opportunities']],
    'data steward' => [SystemRole::DataSteward, ['datasets', 'projects']],
    'academy coordinator' => [SystemRole::AcademyCoordinator, ['courses']],
    'instructor' => [SystemRole::Instructor, ['courses']],
    'innovation officer' => [SystemRole::InnovationOfficer, ['applications']],
    'service desk agent' => [SystemRole::ServiceDeskAgent, ['tickets']],
    'project manager' => [SystemRole::ProjectManager, ['projects']],
    'directorate head' => [SystemRole::DirectorateHead, ['opportunities', 'datasets', 'tickets', 'projects', 'posts']],
    'analyst' => [SystemRole::Analyst, []],
    'admin' => [SystemRole::Admin, ['opportunities', 'datasets', 'courses', 'applications', 'tickets', 'projects', 'posts', 'users']],
]);

it('lets a directorate head read the opportunities of their directorate only', function () {
    $ict = agencyOffice('Sample ICT directorate');
    $other = agencyOffice('Sample other directorate');
    $mine = agencyOpportunity('Sample ICT tender', ['office_id' => $ict->id]);
    $theirs = agencyOpportunity('Sample other tender', ['office_id' => $other->id]);

    $this->actingAs(headOf($ict));
    livewire(ListOpportunities::class)->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$theirs]);
    $this->get(OpportunityResource::getUrl('edit', ['record' => $theirs]))->assertNotFound();
});
