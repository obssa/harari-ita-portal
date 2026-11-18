<?php

namespace App\Domain\Health\Support;

use App\Domain\Academy\Models\CourseTranslation;
use App\Domain\Content\Models\PageTranslation;
use App\Domain\Content\Models\PostTranslation;
use App\Domain\Documents\Models\DocumentTranslation;
use App\Domain\Events\Models\EventTranslation;
use App\Domain\Health\Backups\BackupArchive;
use App\Domain\Health\Backups\RestoreDrill;
use App\Domain\Innovation\Models\ChallengeTranslation;
use App\Domain\Innovation\Models\ProgrammeTranslation;
use App\Domain\Innovation\Models\StartupTranslation;
use App\Domain\OpenData\Models\DatasetTranslation;
use App\Domain\Opportunities\Models\OpportunityTranslation;
use App\Domain\Organisation\Models\Leader;
use App\Domain\Organisation\Models\Office;
use App\Domain\Projects\Models\ProjectTranslation;
use App\Domain\Services\Models\ServiceItemTranslation;
use App\Domain\Translation\Support\Locales;
use App\Models\User;
use App\Settings\IntegrationSettings;
use App\Settings\SecuritySettings;
use App\Settings\SiteSettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * What stands between the site and its launch (ADR-0041, ADR-0042): each item ready, missing (with who supplies it)
 * or deferred by decision. It reads the site as it is; it changes nothing.
 */
final class LaunchCheck
{
    public const READY = 'ready';

    public const MISSING = 'missing';

    public const DEFERRED = 'deferred';

    /** This portal's samples start their title or name with this (the fork's start with "Sample: "). */
    public const SAMPLE_MARK = '[Sample]';

    /** @var array<string, array{0: class-string<Model>, 1: string}> */
    private const SAMPLES = [
        'posts' => [PostTranslation::class, 'title'],
        'pages' => [PageTranslation::class, 'title'],
        'services' => [ServiceItemTranslation::class, 'title'],
        'startups' => [StartupTranslation::class, 'name'],
        'programmes' => [ProgrammeTranslation::class, 'title'],
        'challenges' => [ChallengeTranslation::class, 'title'],
        'projects' => [ProjectTranslation::class, 'title'],
        'datasets' => [DatasetTranslation::class, 'title'],
        'courses' => [CourseTranslation::class, 'title'],
        'opportunities' => [OpportunityTranslation::class, 'title'],
        'events' => [EventTranslation::class, 'title'],
        'documents' => [DocumentTranslation::class, 'title'],
    ];

    /**
     * @return list<array{key: string, status: string, detail: string, owner: string}>
     */
    public static function run(): array
    {
        $items = [];
        $add = function (string $key, string $status, string $detail, string $owner = '') use (&$items): void {
            $items[] = ['key' => $key, 'status' => $status, 'detail' => $detail, 'owner' => $owner];
        };

        // Content: the samples are gone and the Agency's own records are in.
        $samples = self::samples();
        $add('samples', $samples === [] ? self::READY : self::MISSING,
            $samples === [] ? 'No labelled sample content.' : 'Labelled samples remain: '.self::describe($samples).'. Remove them with portal:remove-samples.', 'developer');
        $add('directorates', Office::query()->count() > 0 ? self::READY : self::MISSING, Office::query()->count().' directorates and offices.', 'Agency (INPUTS.md: organisation chart)');
        $add('leadership', Leader::query()->count() > 0 ? self::READY : self::MISSING, Leader::query()->count().' leaders.', 'Agency (INPUTS.md: leadership with consent)');
        $services = ServiceItemTranslation::query()->where('title', 'not like', self::SAMPLE_MARK.'%')->where('title', 'not like', 'Sample%')->distinct()->count('service_item_id');
        $add('services', $services > 0 ? self::READY : self::MISSING, $services.' services that are not samples.', 'Agency (INPUTS.md: services)');

        $legal = PageTranslation::query()->where('locale', 'en')->whereIn('slug', ['privacy', 'terms', 'accessibility'])->get();
        $drafts = $legal->filter(fn (PageTranslation $t): bool => str_contains((string) json_encode($t->getAttribute('body')), 'Draft for legal review'))->count();
        $add('legal_pages', $legal->count() >= 3 && $drafts === 0 ? self::READY : self::MISSING,
            $legal->count().' of 3 legal pages; '.$drafts.' still marked as drafts for legal review.', 'Agency legal adviser');

        // Settings the Agency supplies.
        $site = self::settings(SiteSettings::class);
        $contact = $site instanceof SiteSettings && filled($site->contact_email) && filled($site->contact_phone);
        $add('contact', $contact ? self::READY : self::MISSING, $contact ? 'Contact email and phone set.' : 'Contact email or phone not set (Site settings).', 'Agency (INPUTS.md: contact)');
        $har = Locales::isHidden('har');
        $add('harari', $har ? self::DEFERRED : self::READY, $har ? 'Harari is hidden from visitors until its strings are entered (ADR-0043).' : 'Harari is offered to visitors.', 'Agency translator');

        // Accounts and services.
        $demo = User::query()->where('email', 'like', '%@demo.hita.test')->count();
        $add('demo_accounts', $demo === 0 ? self::READY : self::MISSING, $demo === 0 ? 'No demo accounts.' : "{$demo} demo accounts remain.", 'developer');
        $mailer = (string) config('mail.default');
        $add('mail', in_array($mailer, ['log', 'array'], true) ? self::MISSING : self::READY, "Mail is sent with \"{$mailer}\".", 'Agency (official sender address and SMTP)');
        $integrations = self::settings(IntegrationSettings::class);
        $turnstile = $integrations instanceof IntegrationSettings && filled($integrations->turnstile_site_key) && filled($integrations->turnstile_secret_key);
        $add('turnstile', $turnstile ? self::READY : self::MISSING, $turnstile ? 'Turnstile keys set.' : 'No Turnstile keys: the forms have no bot check (Integrations).', 'Agency (Cloudflare account)');
        $cloudflare = $integrations instanceof IntegrationSettings && filled($integrations->cloudflare_zone_id) && filled($integrations->cloudflare_api_token);
        $add('cloudflare', $cloudflare ? self::READY : self::MISSING, $cloudflare ? 'Edge cache purged on every change.' : 'No Cloudflare token: changes reach visitors when the edge cache expires (up to 10 minutes).', 'Agency (Cloudflare account)');
        $add('sms', self::DEFERRED, 'Text messages go to the log; the SMS gateway is set up after everything else (ADR-0041).', 'Agency (Ethio Telecom account and sender ID)');

        // Security and recovery.
        $security = self::settings(SecuritySettings::class);
        $mfa = $security instanceof SecuritySettings && $security->require_mfa;
        $add('mfa', $mfa ? self::READY : self::MISSING, $mfa ? 'Two-step sign-in is required.' : 'Two-step sign-in is not required yet (Security settings).', 'Super Admin');
        $backups = self::attempt(fn (): array => BackupArchive::all(), []);
        $latest = $backups[0]['date'] ?? null;
        $fresh = $latest instanceof CarbonImmutable && $latest->greaterThan(CarbonImmutable::now()->subDays(2));
        $add('backup', $fresh ? self::READY : self::MISSING, $latest instanceof CarbonImmutable ? 'Latest backup '.$latest->toDateTimeString().' UTC.' : 'No backup found.', 'developer');
        $drills = self::attempt(fn (): array => RestoreDrill::history(), []);
        $drill = $drills[0] ?? null;
        $drillOk = is_array($drill) && ($drill['ok'] ?? false) === true && CarbonImmutable::parse((string) ($drill['at'] ?? '2000-01-01'))->greaterThan(CarbonImmutable::now()->subDays(35));
        $add('restore_drill', $drillOk ? self::READY : self::MISSING, is_array($drill) ? 'Last drill '.($drill['at'] ?? '?').': '.(($drill['ok'] ?? false) ? 'passed' : 'failed').'.' : 'No restore drill yet.', 'developer');

        // Indexing follows the environment.
        $production = app()->environment('production');
        $add('indexing', $production ? self::READY : self::DEFERRED, $production ? 'Search engines may index the site.' : 'The site asks search engines not to index it until APP_ENV=production (after the Agency signs off).', 'Agency sign-off');

        return $items;
    }

    /**
     * @return array<string, int> kind => labelled sample records
     */
    public static function samples(): array
    {
        $found = [];
        foreach (self::SAMPLES as $kind => [$model, $column]) {
            // The fork's samples are labelled "Sample: …", this portal's "[Sample] …".
            $count = self::attempt(fn (): int => $model::query()->where(fn ($q) => $q->where($column, 'like', self::SAMPLE_MARK.'%')
                ->orWhere($column, 'like', 'Sample:%')->orWhere($column, 'like', 'Sample %'))->count(), 0);
            if ($count > 0) {
                $found[$kind] = $count;
            }
        }

        return $found;
    }

    /**
     * @param  array<string, int>  $samples
     */
    private static function describe(array $samples): string
    {
        return implode(', ', array_map(fn (string $kind, int $n): string => "{$kind} {$n}", array_keys($samples), $samples));
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T|null
     */
    private static function settings(string $class): ?object
    {
        return self::attempt(fn () => app($class), null);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $read
     * @param  T  $fallback
     * @return T
     */
    private static function attempt(callable $read, mixed $fallback): mixed
    {
        try {
            return $read();
        } catch (Throwable) {
            return $fallback;
        }
    }
}
