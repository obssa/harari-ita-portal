<?php

namespace App\Console\Commands;

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Innovation\Actions\ReviewStartup;
use App\Domain\Innovation\Actions\SaveChallenge;
use App\Domain\Innovation\Actions\SaveProgramme;
use App\Domain\Innovation\Actions\SaveStartup;
use App\Domain\Innovation\Enums\ListingStatus;
use App\Domain\Innovation\Models\Challenge;
use App\Domain\Innovation\Models\Programme;
use App\Domain\Innovation\Models\Startup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Clearly labelled sample content for the phase 3 gate on staging (docs/phases/03-innovation.md): one listed
 * startup, one programme with an open cohort and one challenge taking teams, in English, Amharic and Afaan Oromo.
 * Nothing is sent to anyone. `--remove` deletes exactly what it added. Refuses to run in production.
 */
final class SampleInnovationCommand extends Command
{
    protected $signature = 'portal:sample-innovation {--remove : Delete the sample content again}';

    protected $description = 'Add (or remove) a labelled sample startup, programme and challenge';

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
        if (Challenge::query()->whereHas('translations', fn ($q) => $q->where('title', 'like', self::MARK.'%'))->exists()) {
            $this->components->warn('Sample content is already there.');

            return self::SUCCESS;
        }

        $actor = User::role(SystemRole::SuperAdmin->value)->firstOrFail();
        $now = CarbonImmutable::now();

        $startup = app(SaveStartup::class)->handle(new Startup, [
            'stage' => 'mvp',
            'founded_year' => (int) $now->format('Y'),
            'team_size' => 4,
            'translations' => [
                'en' => ['name' => self::MARK.' Harar Market Link', 'summary' => 'Sample startup for testing the directory. Not a real company.', 'products' => "Sample price board\nSample SMS alerts"],
                'am' => ['name' => self::MARK.' ሐረር ገበያ ሊንክ', 'summary' => 'ማውጫውን ለመሞከር የቀረበ ናሙና ጀማሪ ኩባንያ። እውነተኛ ኩባንያ አይደለም።'],
                'om' => ['name' => self::MARK.' Harar Market Link', 'summary' => 'Jalqabbii daldalaa fakkeenyaa galmee qoruuf. Dhaabbata dhugaa miti.'],
            ],
        ], $actor);
        app(ReviewStartup::class)->decide($startup, ListingStatus::Listed, null, $actor);

        app(SaveProgramme::class)->handle(new Programme, [
            'kind' => 'incubation',
            'translations' => [
                'en' => [
                    'title' => self::MARK.' Incubation programme',
                    'summary' => 'Sample programme for testing applications. Not a real call.',
                    'eligibility' => 'Sample: early-stage teams in the region.',
                    'benefits' => "Sample mentoring\nSample workspace\nSample demo day",
                    'timeline' => "Sample month 1: training\nSample month 3: pilot\nSample month 6: demo day",
                    'published' => true,
                ],
                'am' => ['title' => self::MARK.' የማቀፊያ ፕሮግራም', 'summary' => 'ማመልከቻዎችን ለመሞከር የቀረበ ናሙና ፕሮግራም። እውነተኛ ጥሪ አይደለም።', 'published' => true],
                'om' => ['title' => self::MARK.' Sagantaa inkubeeshinii', 'summary' => 'Sagantaa fakkeenyaa iyyannoo qoruuf. Waamicha dhugaa miti.', 'published' => true],
            ],
            'questions' => [
                ['labels' => ['en' => 'What problem do you solve? (sample)', 'am' => 'የትኛውን ችግር ይፈታሉ? (ናሙና)', 'om' => 'Rakkoo kam furtu? (fakkeenya)'], 'long' => true, 'required' => true],
            ],
            'cohorts' => [[
                'name' => self::MARK.' Cohort 1',
                'opens_at' => $now->subDay()->toDateTimeString(),
                'closes_at' => $now->addDays(30)->toDateTimeString(),
                'starts_on' => $now->addDays(45)->toDateString(),
                'places' => 10,
            ]],
        ], $actor);

        app(SaveChallenge::class)->handle(new Challenge, [
            'translations' => [
                'en' => [
                    'title' => self::MARK.' Open data hackathon',
                    'summary' => 'Sample challenge for testing teams and judging. Not a real competition.',
                    'prizes' => "Sample first prize\nSample second prize",
                    'rules' => "Sample rule: original work only.\nSample rule: teams of up to four.",
                    'published' => true,
                ],
                'am' => ['title' => self::MARK.' የክፍት መረጃ ሃካቶን', 'summary' => 'ቡድኖችንና ዳኝነትን ለመሞከር የቀረበ ናሙና ውድድር። እውነተኛ ውድድር አይደለም።', 'published' => true],
                'om' => ['title' => self::MARK.' Hakaatoonii daataa banaa', 'summary' => 'Dorgommii fakkeenyaa gareewwanii fi murtii qoruuf. Dorgommii dhugaa miti.', 'published' => true],
            ],
            'registration_opens_at' => $now->subDay()->toDateTimeString(),
            'registration_closes_at' => $now->addDays(14)->toDateTimeString(),
            'submission_closes_at' => $now->addDays(28)->toDateTimeString(),
            'results_on' => $now->addDays(35)->toDateString(),
            'max_team_size' => 4,
            'criteria' => [
                ['labels' => ['en' => 'Impact (sample)', 'am' => 'ተጽዕኖ (ናሙና)', 'om' => 'Dhiibbaa (fakkeenya)'], 'weight' => 3],
                ['labels' => ['en' => 'Use of data (sample)', 'am' => 'የመረጃ አጠቃቀም (ናሙና)', 'om' => 'Itti fayyadama daataa (fakkeenya)'], 'weight' => 2],
                ['labels' => ['en' => 'Feasibility (sample)', 'am' => 'ተግባራዊነት (ናሙና)', 'om' => 'Hojiirra oolmaa (fakkeenya)'], 'weight' => 1],
            ],
        ], $actor);

        $this->components->info('Sample startup, programme and challenge added.');

        return self::SUCCESS;
    }

    private function remove(): int
    {
        $marked = fn (string $field) => fn ($q) => $q->where($field, 'like', self::MARK.'%');
        $challenges = Challenge::query()->withTrashed()->whereHas('translations', $marked('title'))->get();
        $programmes = Programme::query()->withTrashed()->whereHas('translations', $marked('title'))->get();
        $startups = Startup::query()->withTrashed()->whereHas('translations', $marked('name'))->get();

        $challenges->each(function (Challenge $challenge): void {
            $challenge->teams()->each(function ($team): void {
                $team->assignments()->delete();
                $team->delete();
            });
            $challenge->forceDelete();
        });
        $programmes->each(function (Programme $programme): void {
            $programme->cohorts()->each(function ($cohort): void {
                $cohort->applications()->delete();
                $cohort->delete();
            });
            $programme->forceDelete();
        });
        $startups->each(fn (Startup $startup) => $startup->forceDelete());

        $this->components->info(sprintf('Removed %d challenge(s), %d programme(s), %d startup(s).', $challenges->count(), $programmes->count(), $startups->count()));

        return self::SUCCESS;
    }
}
