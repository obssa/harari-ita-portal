<?php

namespace App\Console\Commands;

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Media\Actions\UploadMedia;
use App\Domain\OpenData\Actions\SaveDataset;
use App\Domain\OpenData\Actions\SaveDistribution;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\OpenData\Models\DatasetDistribution;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Projects\Actions\RecordKpiReading;
use App\Domain\Projects\Actions\SaveKpi;
use App\Domain\Projects\Actions\SaveProject;
use App\Domain\Projects\Actions\SaveRisk;
use App\Domain\Projects\Actions\SaveStatistic;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectKpi;
use App\Domain\Projects\Models\ProjectRisk;
use App\Domain\Projects\Models\Statistic;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Clearly labelled sample content for the phase 4 gate on staging (docs/phases/04-projects-open-data.md): one project
 * with milestones, an indicator with readings, a risk and a map pin; one open dataset with a CSV file of made-up
 * figures; and one statistic linked to it, in English, Amharic and Afaan Oromo. Every number is invented and says so.
 * `--remove` deletes exactly what it added. Refuses to run in production.
 */
final class SampleProjectsDataCommand extends Command
{
    protected $signature = 'portal:sample-projects-data {--remove : Delete the sample content again}';

    protected $description = 'Add (or remove) a labelled sample project, dataset and statistic';

    private const MARK = '[Sample]';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->components->error('Not in production.');

            return self::FAILURE;
        }

        return $this->option('remove') ? $this->remove() : $this->add();
    }

    private function add(): int
    {
        if (Dataset::query()->whereHas('translations', fn ($q) => $q->where('title', 'like', self::MARK.'%'))->exists()) {
            $this->components->warn('Sample content is already there.');

            return self::SUCCESS;
        }

        $actor = User::role(SystemRole::SuperAdmin->value)->firstOrFail();
        $today = CarbonImmutable::today();
        $pillar = Pillar::query()->orderBy('sort')->first();

        $dataset = app(SaveDataset::class)->handle(new Dataset, [
            'pillar_id' => $pillar?->id,
            'licence' => 'cc-by-4.0',
            'frequency' => 'monthly',
            'keywords' => ['sample', 'wi-fi'],
            'temporal_from' => $today->subMonths(5)->startOfMonth()->toDateString(),
            'temporal_to' => $today->subMonth()->endOfMonth()->toDateString(),
            'translations' => [
                'en' => ['title' => self::MARK.' Public Wi-Fi sessions by month', 'summary' => 'Sample dataset for testing the catalogue. The figures are invented.', 'coverage' => 'Sample area', 'methodology' => 'Sample: not real data.', 'published' => true],
                'am' => ['title' => self::MARK.' የሕዝብ ዋይ-ፋይ አጠቃቀም በወር', 'summary' => 'ካታሎጉን ለመሞከር የቀረበ ናሙና የመረጃ ስብስብ። አሃዞቹ የተፈጠሩ ናቸው።', 'published' => true],
                'om' => ['title' => self::MARK.' Itti fayyadama Wi-Fi ummataa ji\'aan', 'summary' => 'Daataa fakkeenyaa kaataalogii qoruuf. Lakkoofsonni uumamoodha.', 'published' => true],
            ],
        ], $actor);

        $rows = ['month,sessions,sample_note'];
        foreach (range(5, 1) as $i => $back) {
            $rows[] = $today->subMonths($back)->format('Y-m').','.(1200 + $i * 350).',invented';
        }
        $path = storage_path('app/sample-wifi-sessions.csv');
        file_put_contents($path, implode("\n", $rows)."\n");
        try {
            $media = app(UploadMedia::class)->handle($path, 'sample-wifi-sessions.csv', $actor);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
        app(SaveDistribution::class)->handle($dataset, new DatasetDistribution, ['format' => 'csv', 'media_id' => $media->id, 'titles' => ['en' => self::MARK.' Monthly sessions']], $actor);

        $project = app(SaveProject::class)->handle(new Project, [
            'status' => 'ongoing',
            'pillar_id' => $pillar?->id,
            'rag_status' => 'amber',
            'rag_reason' => self::MARK.' internal note',
            'starts_on' => $today->subMonths(6)->toDateString(),
            'ends_on' => $today->addMonths(6)->toDateString(),
            'progress' => 45,
            'latitude' => '9.3110',
            'longitude' => '42.1260',
            'milestones' => [
                ['titles' => ['en' => self::MARK.' Survey of sites', 'am' => self::MARK.' የቦታዎች ጥናት', 'om' => self::MARK.' Qorannoo bakkeewwanii'], 'due_on' => $today->subMonths(4)->toDateString(), 'done_on' => $today->subMonths(4)->subDays(3)->toDateString()],
                ['titles' => ['en' => self::MARK.' First access points', 'am' => self::MARK.' የመጀመሪያ መዳረሻ ነጥቦች', 'om' => self::MARK.' Qabxiilee seensaa jalqabaa'], 'due_on' => $today->subWeeks(2)->toDateString()],
                ['titles' => ['en' => self::MARK.' Hand-over', 'am' => self::MARK.' ርክክብ', 'om' => self::MARK.' Harka kennaa'], 'due_on' => $today->addMonths(6)->toDateString()],
            ],
            'translations' => [
                'en' => ['title' => self::MARK.' Public Wi-Fi pilot', 'summary' => 'Sample project for testing the portfolio. Not a real project.', 'location' => 'Sample location', 'published' => true],
                'am' => ['title' => self::MARK.' የሕዝብ ዋይ-ፋይ ሙከራ', 'summary' => 'ፖርትፎሊዮውን ለመሞከር የቀረበ ናሙና ፕሮጀክት። እውነተኛ ፕሮጀክት አይደለም።', 'published' => true],
                'om' => ['title' => self::MARK.' Yaalii Wi-Fi ummataa', 'summary' => 'Pirojektii fakkeenyaa qoruuf. Pirojektii dhugaa miti.', 'published' => true],
            ],
        ], $actor);

        $kpi = app(SaveKpi::class)->handle($project, new ProjectKpi, [
            'names' => ['en' => self::MARK.' Access points working', 'am' => self::MARK.' የሚሠሩ መዳረሻ ነጥቦች', 'om' => self::MARK.' Qabxiilee seensaa hojjetan'],
            'baseline' => 0,
            'target' => 20,
        ], $actor);
        foreach ([3 => 2, 2 => 5, 1 => 9] as $back => $value) {
            app(RecordKpiReading::class)->handle($kpi, ['value' => $value, 'as_of' => $today->subMonths($back)->toDateString()], $actor);
        }
        app(SaveRisk::class)->handle($project, new ProjectRisk, ['title' => self::MARK.' Equipment arrives late', 'likelihood' => 'medium', 'impact' => 'high'], $actor);

        app(SaveStatistic::class)->handle(new Statistic, [
            'value' => 9,
            'as_of' => $today->subMonth()->toDateString(),
            'dataset_id' => $dataset->id,
            'translations' => [
                'en' => ['label' => self::MARK.' Wi-Fi access points working', 'source' => 'Sample source (invented)'],
                'am' => ['label' => self::MARK.' የሚሠሩ የዋይ-ፋይ መዳረሻ ነጥቦች', 'source' => 'ናሙና ምንጭ (የተፈጠረ)'],
                'om' => ['label' => self::MARK.' Qabxiilee seensaa Wi-Fi hojjetan', 'source' => 'Madda fakkeenyaa (uumamaa)'],
            ],
        ], $actor);

        $this->components->info('Sample project, dataset and statistic added.');

        return self::SUCCESS;
    }

    private function remove(): int
    {
        $marked = fn (string $field) => fn ($q) => $q->where($field, 'like', self::MARK.'%');
        $statistics = Statistic::query()->whereHas('translations', $marked('label'))->get();
        $projects = Project::query()->withTrashed()->whereHas('translations', $marked('title'))->get();
        $datasets = Dataset::query()->withTrashed()->whereHas('translations', $marked('title'))->with('distributions.media')->get();

        $statistics->each(fn (Statistic $statistic) => $statistic->delete());
        $projects->each(fn (Project $project) => $project->forceDelete());
        $datasets->each(function (Dataset $dataset): void {
            $media = $dataset->distributions->pluck('media')->filter();
            $dataset->forceDelete();
            $media->each(fn ($file) => $file->delete());
        });

        $this->components->info(sprintf('Removed %d project(s), %d dataset(s), %d statistic(s).', $projects->count(), $datasets->count(), $statistics->count()));

        return self::SUCCESS;
    }
}
