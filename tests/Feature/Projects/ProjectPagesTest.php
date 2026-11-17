<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Organisation\Actions\SavePillar;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Projects\Actions\RecordKpiReading;
use App\Domain\Projects\Actions\SaveKpi;
use App\Domain\Projects\Actions\SaveProject;
use App\Domain\Projects\Actions\SaveRisk;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectKpi;
use App\Domain\Projects\Models\ProjectRisk;

/**
 * @param  array<string, mixed>  $data
 */
function publicProject(string $title, array $data = []): Project
{
    return app(SaveProject::class)->handle(new Project, [
        'translations' => ['en' => ['title' => $title, 'published' => true]],
        'status' => 'ongoing',
        ...$data,
    ], staff(SystemRole::ProjectManager));
}

it('shows a running project with its RAG status, milestones, indicators and spend, but never its risks or the RAG reason', function () {
    $manager = staff(SystemRole::ProjectManager);
    $project = publicProject('Sample connected schools', [
        'rag_status' => 'amber', 'rag_reason' => 'Sample internal reason',
        'budget' => '2000000', 'budget_public' => true, 'spent' => '500000',
        'milestones' => [
            ['titles' => ['en' => 'Sample survey'], 'due_on' => '2026-01-10', 'done_on' => '2026-01-09'],
            ['titles' => ['en' => 'Sample installation'], 'due_on' => '2026-02-01'],
        ],
    ]);
    $kpi = app(SaveKpi::class)->handle($project, new ProjectKpi, ['names' => ['en' => 'Sample schools online'], 'units' => ['en' => 'schools'], 'baseline' => 0, 'target' => 40], $manager);
    app(RecordKpiReading::class)->handle($kpi, ['value' => 10, 'as_of' => '2026-01-15'], $manager);
    app(RecordKpiReading::class)->handle($kpi, ['value' => 18, 'as_of' => '2026-02-15'], $manager);
    app(SaveRisk::class)->handle($project, new ProjectRisk, ['title' => 'Sample hidden risk', 'likelihood' => 'high', 'impact' => 'high'], $manager);

    $this->get('/en/projects/sample-connected-schools')->assertOk()
        ->assertSee('At risk')
        ->assertSee('Sample survey')->assertSee('Sample installation')->assertSee('Overdue')
        ->assertSee('1 of 2 done')
        ->assertSee('Sample schools online')->assertSee('Readings of Sample schools online over time', false)
        ->assertSee('45%')
        ->assertSee('Spent so far')->assertSee('500,000')
        ->assertDontSee('Sample hidden risk')
        ->assertDontSee('Sample internal reason');

    // Spend stays private with the budget, and the RAG status is shown only while the project runs.
    app(SaveProject::class)->handle($project, [
        'translations' => ['en' => ['title' => 'Sample connected schools', 'published' => true]],
        'status' => 'completed', 'rag_status' => 'amber', 'budget' => '2000000', 'spent' => '500000',
    ], $manager);
    $this->get('/en/projects/sample-connected-schools')->assertOk()
        ->assertDontSee('At risk')->assertDontSee('Spent so far')->assertDontSee('500,000');
});

it('filters the portfolio by pillar and RAG status, and plots the projects that have a map pin', function () {
    $pillar = app(SavePillar::class)->handle(new Pillar, ['translations' => ['en' => ['name' => 'Sample data pillar', 'published' => true]]], staff(SystemRole::CommunicationsEditor));
    publicProject('Sample red project', ['rag_status' => 'red', 'pillar_id' => $pillar->id, 'latitude' => '9.31', 'longitude' => '42.12']);
    publicProject('Sample green project', ['rag_status' => 'green', 'latitude' => '9.32', 'longitude' => '42.14']);
    publicProject('Sample planned red', ['status' => 'planned', 'rag_status' => 'red']);

    $this->get('/en/projects')->assertOk()
        ->assertSee('Where the projects are')
        ->assertSee('2 projects with a map location')
        ->assertSee('aria-label="Sample red project"', false);

    $this->get('/en/projects?pillar='.$pillar->id)->assertOk()
        ->assertSee('Sample red project')->assertDontSee('Sample green project');

    $this->get('/en/projects?rag=red')->assertOk()
        ->assertSee('Sample red project')->assertDontSee('Sample green project')->assertDontSee('Sample planned red');
});
