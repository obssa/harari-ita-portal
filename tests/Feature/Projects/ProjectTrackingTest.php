<?php

use App\Domain\Access\Enums\ScopeType;
use App\Domain\Access\Enums\SystemRole;
use App\Domain\Organisation\Actions\SaveOffice;
use App\Domain\Organisation\Actions\SavePillar;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Projects\Actions\RecordKpiReading;
use App\Domain\Projects\Actions\SaveKpi;
use App\Domain\Projects\Actions\SaveProject;
use App\Domain\Projects\Actions\SaveRisk;
use App\Domain\Projects\Enums\RagStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectKpi;
use App\Domain\Projects\Models\ProjectRisk;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\RelationManagers\KpisRelationManager;
use App\Filament\Resources\Projects\RelationManagers\RisksRelationManager;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

function trackedOffice(string $name): Office
{
    return app(SaveOffice::class)->handle(new Office, ['type' => 'directorate', 'translations' => ['en' => ['name' => $name, 'published' => true]]], staff(SystemRole::CommunicationsEditor));
}

/**
 * @param  array<string, mixed>  $data
 */
function trackedProject(array $data = [], ?User $actor = null): Project
{
    return app(SaveProject::class)->handle(new Project, [
        'translations' => ['en' => ['title' => 'Sample tracked project', 'published' => true]],
        'status' => 'ongoing',
        ...$data,
    ], $actor ?? staff(SystemRole::ProjectManager));
}

it('saves the pillar, RAG status, spend and milestones of a project', function () {
    $pillar = app(SavePillar::class)->handle(new Pillar, ['translations' => ['en' => ['name' => 'Sample pillar', 'published' => true]]], staff(SystemRole::CommunicationsEditor));
    $project = trackedProject([
        'pillar_id' => $pillar->id,
        'rag_status' => 'amber',
        'rag_reason' => 'Sample: supplier late',
        'budget' => '1000000',
        'budget_public' => true,
        'spent' => '250000',
        'milestones' => [
            ['titles' => ['en' => 'Sample design', 'am' => 'ናሙና ንድፍ'], 'due_on' => '2026-01-10', 'done_on' => '2026-01-08'],
            ['titles' => ['en' => 'Sample build'], 'due_on' => '2026-02-01'],
        ],
    ]);

    expect($project->pillar_id)->toBe($pillar->id)
        ->and($project->rag_status)->toBe(RagStatus::Amber)
        ->and($project->rag_updated_at)->not->toBeNull()
        ->and($project->publicRag())->toBe(RagStatus::Amber)
        ->and($project->publicSpent())->toBe(250000.0)
        ->and($project->milestones)->toHaveCount(2)
        ->and($project->milestones[0]->title('am'))->toBe('ናሙና ንድፍ')
        ->and($project->milestones[0]->isDone())->toBeTrue()
        ->and($project->milestones[1]->isOverdue(CarbonImmutable::parse('2026-03-01')))->toBeTrue();

    // Editing keeps the milestones it is given, in that order, and removes the others.
    $kept = $project->milestones[1];
    $project = app(SaveProject::class)->handle($project, [
        'translations' => ['en' => ['title' => 'Sample tracked project', 'published' => true]],
        'status' => 'completed',
        'rag_status' => 'amber',
        'budget' => '1000000',
        'spent' => '900000',
        'milestones' => [['id' => $kept->id, 'titles' => ['en' => 'Sample build, renamed'], 'done_on' => '2026-02-03']],
    ], staff(SystemRole::ProjectManager));

    expect($project->milestones)->toHaveCount(1)
        ->and($project->milestones[0]->id)->toBe($kept->id)
        ->and($project->milestones[0]->title())->toBe('Sample build, renamed')
        ->and($project->publicRag())->toBeNull()
        ->and($project->publicSpent())->toBeNull();

    expect(fn () => trackedProject(['milestones' => [['titles' => ['en' => '']]]]))->toThrow(ValidationException::class)
        ->and(fn () => trackedProject(['milestones' => [['titles' => ['en' => 'Sample'], 'done_on' => CarbonImmutable::tomorrow()->toDateString()]]]))->toThrow(ValidationException::class)
        ->and(fn () => trackedProject(['spent' => -1]))->toThrow(ValidationException::class);
});

it('measures KPIs against their targets from dated readings, one per date', function () {
    $manager = staff(SystemRole::ProjectManager);
    $project = trackedProject();
    $kpi = app(SaveKpi::class)->handle($project, new ProjectKpi, ['names' => ['en' => 'Sample schools connected'], 'baseline' => 10, 'target' => 50], $manager);
    $lower = app(SaveKpi::class)->handle($project, new ProjectKpi, ['names' => ['en' => 'Sample outage hours'], 'baseline' => 40, 'target' => 10, 'higher_is_better' => false], $manager);

    app(RecordKpiReading::class)->handle($kpi, ['value' => 20, 'as_of' => '2026-01-01'], $manager);
    app(RecordKpiReading::class)->handle($kpi, ['value' => 30, 'as_of' => '2026-02-01'], $manager);
    app(RecordKpiReading::class)->handle($kpi, ['value' => 25, 'as_of' => '2026-02-01', 'note' => 'Sample correction'], $manager);
    app(RecordKpiReading::class)->handle($lower, ['value' => 10, 'as_of' => '2026-02-01'], $manager);

    $kpi->refresh()->load('latestReading');
    $lower->refresh()->load('latestReading');
    expect($kpi->readings()->count())->toBe(2)
        ->and((float) $kpi->latestReading->value)->toBe(25.0)
        ->and($kpi->progress())->toBe(38)
        ->and($kpi->met())->toBeFalse()
        ->and($lower->progress())->toBe(100)
        ->and($lower->met())->toBeTrue();

    expect(fn () => app(RecordKpiReading::class)->handle($kpi, ['value' => 1, 'as_of' => CarbonImmutable::tomorrow()->toDateString()], $manager))->toThrow(ValidationException::class)
        ->and(fn () => app(RecordKpiReading::class)->handle($kpi, ['value' => 'many', 'as_of' => '2026-01-05'], $manager))->toThrow(ValidationException::class)
        ->and(fn () => app(SaveKpi::class)->handle($project, new ProjectKpi, ['names' => ['en' => ''], 'target' => 5], $manager))->toThrow(ValidationException::class)
        ->and(fn () => app(RecordKpiReading::class)->handle($kpi, ['value' => 1, 'as_of' => '2026-01-05'], staff(SystemRole::DataSteward)))->toThrow(AuthorizationException::class);
});

it('keeps a scoped project manager to the projects of their own directorate', function () {
    $own = trackedOffice('Sample own directorate');
    $other = trackedOffice('Sample other directorate');
    $manager = User::factory()->create();
    $manager->assignScopedRole(SystemRole::ProjectManager, ScopeType::Office, $own->id);
    $manager->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP'])->saveQuietly();

    $mine = trackedProject(['office_id' => $own->id], $manager);
    $theirs = trackedProject(['office_id' => $other->id]);

    expect($manager->can('update', $mine))->toBeTrue()
        ->and($manager->can('update', $theirs))->toBeFalse()
        ->and($manager->can('view', $theirs))->toBeFalse()
        ->and(fn () => trackedProject(['office_id' => $other->id], $manager))->toThrow(ValidationException::class)
        ->and(fn () => app(SaveKpi::class)->handle($theirs, new ProjectKpi, ['names' => ['en' => 'Sample'], 'target' => 1], $manager))->toThrow(AuthorizationException::class)
        ->and(fn () => app(SaveRisk::class)->handle($theirs, new ProjectRisk, ['title' => 'Sample risk', 'likelihood' => 'low', 'impact' => 'low'], $manager))->toThrow(AuthorizationException::class);

    $this->actingAs($manager);
    livewire(ListProjects::class)->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$theirs]);
});

it('keeps a risk register for staff, scored by likelihood and impact, with an owner who can see the project', function () {
    $manager = staff(SystemRole::ProjectManager);
    $project = trackedProject();
    $risk = app(SaveRisk::class)->handle($project, new ProjectRisk, [
        'title' => 'Sample: equipment arrives late', 'likelihood' => 'high', 'impact' => 'medium', 'owner_id' => $manager->id, 'review_on' => '2026-03-01',
    ], $manager);

    expect($risk->score())->toBe(6)
        ->and($risk->status->value)->toBe('open')
        ->and(fn () => app(SaveRisk::class)->handle($project, new ProjectRisk, ['title' => 'Sample', 'likelihood' => 'high', 'impact' => 'low', 'owner_id' => staff(SystemRole::Author)->id], $manager))->toThrow(ValidationException::class)
        ->and(fn () => app(SaveRisk::class)->handle($project, new ProjectRisk, ['title' => 'Sample', 'likelihood' => 'huge', 'impact' => 'low'], $manager))->toThrow(ValidationException::class);

    // Risks and the RAG reason never reach the public page or the API.
    $this->get('/en/projects/sample-tracked-project')->assertOk()
        ->assertDontSee('Sample: equipment arrives late');
});

it('shows the KPIs and risks on the project page in the admin', function () {
    $manager = staff(SystemRole::ProjectManager);
    $project = trackedProject();
    app(SaveKpi::class)->handle($project, new ProjectKpi, ['names' => ['en' => 'Sample offices online'], 'target' => 12], $manager);
    app(SaveRisk::class)->handle($project, new ProjectRisk, ['title' => 'Sample power cuts', 'likelihood' => 'medium', 'impact' => 'high'], $manager);

    $this->actingAs($manager);
    livewire(EditProject::class, ['record' => $project->getKey()])->assertOk()
        ->assertFormSet(['translations.en.title' => 'Sample tracked project', 'status' => 'ongoing']);
    livewire(KpisRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
        ->assertOk()->assertSee('Sample offices online')
        ->callTableAction('reading', ProjectKpi::query()->firstOrFail(), ['value' => 3, 'as_of' => CarbonImmutable::today()->toDateString()])
        ->assertHasNoTableActionErrors();
    livewire(RisksRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
        ->assertOk()->assertSee('Sample power cuts');

    expect(ProjectKpi::query()->firstOrFail()->readings()->count())->toBe(1);
});
