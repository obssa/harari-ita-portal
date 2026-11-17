<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\Statistic;

it('adds a labelled sample project, dataset and statistic once, and removes exactly them again', function () {
    staff(SystemRole::SuperAdmin);

    $this->artisan('portal:sample-projects-data')->assertSuccessful();
    $this->artisan('portal:sample-projects-data')->assertSuccessful();

    $project = Project::query()->sole();
    $dataset = Dataset::query()->sole();
    expect($project->milestones)->toHaveCount(3)
        ->and($project->kpis()->sole()->readings()->count())->toBe(3)
        ->and($project->risks()->count())->toBe(1)
        ->and($dataset->distributions()->sole()->row_count)->toBe(5)
        ->and(Statistic::query()->sole()->dataset_id)->toBe($dataset->id);

    $this->get('/en/projects/sample-public-wi-fi-pilot')->assertOk()->assertSee('[Sample] First access points')->assertDontSee('[Sample] Equipment arrives late');
    $this->get('/en/data/sample-public-wi-fi-sessions-by-month')->assertOk()->assertSee('invented');

    $this->artisan('portal:sample-projects-data', ['--remove' => true])->assertSuccessful();
    expect(Project::query()->withTrashed()->count())->toBe(0)
        ->and(Dataset::query()->withTrashed()->count())->toBe(0)
        ->and(Statistic::query()->count())->toBe(0);
});
