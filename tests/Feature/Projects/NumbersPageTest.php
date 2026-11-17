<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\OpenData\Actions\SaveDataset;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\Organisation\Actions\SavePillar;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Projects\Actions\SaveProject;
use App\Domain\Projects\Actions\SaveStatistic;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\Statistic;

it('counts live figures from the records, charts projects by status and pillar, and links a statistic to its dataset', function () {
    $manager = staff(SystemRole::ProjectManager);
    $pillar = app(SavePillar::class)->handle(new Pillar, ['translations' => ['en' => ['name' => 'Sample skills pillar', 'published' => true]]], staff(SystemRole::CommunicationsEditor));
    foreach (['Sample one', 'Sample two'] as $title) {
        app(SaveProject::class)->handle(new Project, ['translations' => ['en' => ['title' => $title, 'published' => true]], 'status' => 'ongoing', 'pillar_id' => $pillar->id], $manager);
    }
    $dataset = app(SaveDataset::class)->handle(new Dataset, ['translations' => ['en' => ['title' => 'Sample trained people', 'published' => true]]], staff(SystemRole::DataSteward));
    app(SaveStatistic::class)->handle(new Statistic, ['translations' => ['en' => ['label' => 'Sample people trained', 'source' => 'Sample source']], 'value' => 480, 'dataset_id' => $dataset->id], $manager);

    $this->get('/en/numbers')->assertOk()
        ->assertSee('Projects published')
        ->assertSee('Open datasets')
        ->assertSee('Projects by pillar')->assertSee('Sample skills pillar')
        ->assertSee('2 projects')
        ->assertSee('Sample people trained')->assertSee('480')
        ->assertSee('Source dataset')->assertSee(url('/en/data/sample-trained-people'), false);

    $this->get('/am/numbers')->assertOk()->assertSee('ዲጂታል ሐረር በቁጥሮች');
});
