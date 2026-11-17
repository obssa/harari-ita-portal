<?php

namespace App\Console\Commands;

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Content\Actions\SavePost;
use App\Domain\Content\Models\Post;
use App\Domain\Engagement\Actions\DraftCampaign;
use App\Domain\Engagement\Enums\CampaignStatus;
use App\Domain\Engagement\Models\Campaign;
use App\Domain\Import\Support\StoryText;
use App\Domain\Opportunities\Actions\PublishAddendum;
use App\Domain\Opportunities\Actions\SaveOpportunity;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Organisation\Models\Pillar;
use App\Models\User;
use App\Services\Calendar\EthiopianCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Clearly labelled sample content for the phase 6 gate on staging (docs/phases/06-opportunities-advisories.md): a
 * tender with an addendum, a clarification and an extension; a vacancy; a call for proposals closing soon; an
 * internship that has closed; and an advisory in English, Amharic and Afaan Oromo with a campaign drafted from it
 * (never sent). `--closes-in=N` adds a tender closing in N minutes, to watch the automatic archive. `--remove`
 * deletes exactly what it added. Refuses to run in production.
 */
final class SampleOpportunitiesCommand extends Command
{
    protected $signature = 'portal:sample-opportunities {--remove : Delete the sample content again} {--closes-in= : Also add a tender closing in this many minutes}';

    protected $description = 'Add (or remove) labelled sample opportunities and a sample advisory';

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
        $actor = User::role(SystemRole::SuperAdmin->value)->firstOrFail();
        $save = app(SaveOpportunity::class);
        $minutes = $this->option('closes-in');
        if (is_numeric($minutes) && (int) $minutes > 0) {
            $tender = $save->handle(new Opportunity, $this->tender(['en' => 'Tender closing shortly', 'am' => 'በቅርቡ የሚዘጋ ጨረታ', 'om' => 'Caalbaasii dhiyootti cufamu'], CarbonImmutable::now()->addMinutes((int) $minutes), 'SAMPLE/T/002'), $actor);
            $this->line('Closing tender: '.route('opportunities.show', ['locale' => 'en', 'slug' => $tender->translation('en')?->getAttribute('slug')]).' at '.$tender->closes_at->toIso8601String());
        }

        if (Opportunity::query()->whereHas('translations', fn ($q) => $q->where('locale', 'en')->where('title', 'like', self::MARK.' Supply%'))->exists()) {
            $this->components->warn('Sample content is already there.');

            return self::SUCCESS;
        }

        // Deadlines at 10:00 in Ethiopia, stored in UTC.
        $at = fn (int $days, string $time = '10:00'): CarbonImmutable => CarbonImmutable::parse(CarbonImmutable::today(EthiopianCalendar::timezone())->addDays($days)->toDateString().' '.$time, EthiopianCalendar::timezone())->utc();
        $pillar = Pillar::query()->orderBy('sort')->value('id');

        $tender = $save->handle(new Opportunity, [...$this->tender(['en' => 'Supply of network equipment for regional offices', 'am' => 'ለክልል ጽሕፈት ቤቶች የኔትወርክ መሣሪያዎች አቅርቦት', 'om' => 'Dhiyeessii meeshaalee neetworkii waajjiraalee naannootiif'], $at(14), 'SAMPLE/T/001'), 'bid_opening_at' => $at(14, '10:30')->toDateTimeString(), 'pillar_id' => $pillar], $actor);
        $log = app(PublishAddendum::class);
        $log->handle($tender, ['kind' => 'addendum', 'titles' => [
            'en' => 'Revised quantity of switches in lot 2', 'am' => 'በሎት 2 የተሻሻለ የስዊች ብዛት', 'om' => 'Baay\'ina swiichii lootii 2 fooyya\'e',
        ], 'bodies' => [
            'en' => 'Sample entry: lot 2 now asks for 12 switches instead of 10.', 'am' => 'ናሙና ምዝገባ፦ ሎት 2 አሁን ከ10 ይልቅ 12 ስዊቾችን ይጠይቃል።', 'om' => 'Galmee fakkeenyaa: lootiin 2 amma swiichii 10 osoo hin taane 12 gaafata.',
        ]], $actor);
        $log->handle($tender, ['kind' => 'clarification', 'titles' => [
            'en' => 'May a bidder offer equivalent brands?', 'am' => 'ተጫራች ተመጣጣኝ የንግድ ምልክቶችን ማቅረብ ይችላል?', 'om' => 'Dorgomaan maqaa daldalaa walfakkaatu dhiyeessuu ni danda\'aa?',
        ], 'bodies' => [
            'en' => 'Sample answer: yes, when they meet the technical specification.', 'am' => 'ናሙና መልስ፦ አዎ፣ የቴክኒክ መስፈርቱን ካሟሉ።', 'om' => 'Deebii fakkeenyaa: eeyyee, ibsa teeknikaa yoo guutan.',
        ]], $actor);
        $log->handle($tender, ['kind' => 'extension', 'new_closes_at' => $at(21)->toDateTimeString(), 'titles' => [
            'en' => 'Deadline extended by one week', 'am' => 'የማብቂያ ቀኑ በአንድ ሳምንት ተራዝሟል', 'om' => 'Guyyaan xumuraa torban tokkoon dheereffame',
        ]], $actor);

        $save->handle(new Opportunity, [
            'type' => 'vacancy', 'reference' => 'SAMPLE/V/001', 'opens_at' => null, 'closes_at' => $at(10)->toDateTimeString(),
            'positions' => 2, 'employment_type' => 'full_time', 'pillar_id' => $pillar,
            'translations' => $this->texts(['en' => 'Junior systems administrator', 'am' => 'ጀማሪ የሥርዓት አስተዳዳሪ', 'om' => 'Bulchaa sirnaa jalqabaa'], [
                'en' => 'Sample vacancy for testing the opportunities pages. Not a real position.',
                'am' => 'የዕድሎች ገጾችን ለመሞከር የቀረበ ናሙና ክፍት የሥራ ቦታ። እውነተኛ የሥራ ቦታ አይደለም።',
                'om' => 'Bakka hojii fakkeenyaa fuulota carraawwanii qorachuuf. Bakka hojii dhugaa miti.',
            ], ['en' => "Sample: a diploma or degree in IT\nSample: one year of experience", 'am' => "ናሙና፦ በአይቲ ዲፕሎማ ወይም ዲግሪ\nናሙና፦ የአንድ ዓመት ልምድ", 'om' => "Fakkeenya: dippilomaa ykn digrii IT\nFakkeenya: muuxannoo waggaa tokkoo"]),
        ], $actor);

        $save->handle(new Opportunity, [
            'type' => 'call', 'reference' => 'SAMPLE/C/001', 'closes_at' => $at(3)->toDateTimeString(), 'pillar_id' => $pillar,
            'translations' => $this->texts(['en' => 'Call for proposals: digital services for kebele offices', 'am' => 'የፕሮፖዛል ጥሪ፦ ለቀበሌ ጽሕፈት ቤቶች ዲጂታል አገልግሎቶች', 'om' => 'Waamicha piroppoozaalii: tajaajila dijitaalaa waajjiraalee gandaatiif'], [
                'en' => 'Sample call for testing the opportunities pages. Not a real call.',
                'am' => 'የዕድሎች ገጾችን ለመሞከር የቀረበ ናሙና ጥሪ። እውነተኛ ጥሪ አይደለም።',
                'om' => 'Waamicha fakkeenyaa fuulota carraawwanii qorachuuf. Waamicha dhugaa miti.',
            ]),
        ], $actor);

        $closed = new Opportunity;
        $save->handle($closed, [
            'type' => 'internship', 'reference' => 'SAMPLE/I/001', 'opens_at' => $at(-30)->toDateTimeString(), 'closes_at' => $at(-5)->toDateTimeString(), 'positions' => 4,
            'translations' => $this->texts(['en' => 'Software development internship', 'am' => 'የሶፍትዌር ልማት የተግባር ልምምድ', 'om' => 'Hojii shaakalaa misooma sooftiweerii'], [
                'en' => 'Sample internship that has closed, to show the archive. Not a real internship.',
                'am' => 'ማህደሩን ለማሳየት የተዘጋ ናሙና የተግባር ልምምድ። እውነተኛ አይደለም።',
                'om' => 'Hojii shaakalaa fakkeenyaa cufame, kuusaa agarsiisuuf. Kan dhugaa miti.',
            ], null, ['en' => 'Sample outcome: four interns were selected.', 'am' => 'ናሙና ውጤት፦ አራት ሰልጣኞች ተመርጠዋል።', 'om' => 'Bu\'aa fakkeenyaa: leenjifamtoonni afur filatamaniiru.']),
        ], $actor);

        $advisory = app(SavePost::class)->handle(new Post, [
            'type' => 'advisory', 'severity' => 'warning', 'source_locale' => 'en',
            'translations' => [
                'en' => $this->advisoryText('Phishing messages pretending to be the Agency', 'Sample advisory for testing. Messages ask staff to "confirm" their password on a fake page.', "Sample: staff email accounts\nSample: the support desk", "Do not open links in unexpected messages.\nCheck the sender's address.\nChange your password if you entered it on another site.\nTell IT support."),
                'am' => $this->advisoryText('ኤጀንሲውን መስለው የሚላኩ የማጭበርበሪያ መልእክቶች', 'ለሙከራ የቀረበ ናሙና ምክር። መልእክቶቹ ሠራተኞች የይለፍ ቃላቸውን በሐሰተኛ ገጽ ላይ "እንዲያረጋግጡ" ይጠይቃሉ።', "ናሙና፦ የሠራተኞች የኢሜይል መለያዎች\nናሙና፦ የድጋፍ ዴስክ", "ባልጠበቁት መልእክት ውስጥ ያሉ አገናኞችን አይክፈቱ።\nየላኪውን አድራሻ ያረጋግጡ።\nየይለፍ ቃልዎን በሌላ ገጽ ላይ ካስገቡ ይቀይሩት።\nለአይቲ ድጋፍ ያሳውቁ።"),
                'om' => $this->advisoryText('Ergaawwan gowwoomsaa Ejansicha fakkeessan', 'Gorsa fakkeenyaa qorannoof. Ergaawwan hojjettoonni jecha darbii isaanii fuula sobaa irratti akka "mirkaneessan" gaafatu.', "Fakkeenya: herrega imeelii hojjettootaa\nFakkeenya: deeskii deeggarsaa", "Hidhaawwan ergaa hin eegamne keessa jiran hin banina.\nTeessoo ergaa erguu mirkaneessaa.\nJecha darbii keessan marsariitii biraa irratti yoo galchitan jijjiiraa.\nDeeggarsa IT beeksisaa."),
            ],
        ], $actor);
        $campaign = app(DraftCampaign::class)->handle($advisory, $actor);

        $this->call('opportunities:archive');
        $this->components->info('Sample opportunities and advisory added.');
        $this->line(route('opportunities.index', ['locale' => 'en']));
        $this->line(route('opportunities.show', ['locale' => 'en', 'slug' => $tender->translation('en')?->getAttribute('slug')]));
        $this->line($advisory->url($advisory->translations()->where('locale', 'en')->firstOrFail()));
        $this->line('Campaign draft #'.$campaign->id.' (not sent): '.implode(', ', $campaign->languages()));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $title
     * @return array<string, mixed>
     */
    private function tender(array $title, CarbonImmutable $closes, string $reference): array
    {
        return [
            'type' => 'tender', 'reference' => $reference, 'closes_at' => $closes->toDateTimeString(),
            'translations' => $this->texts($title, [
                'en' => 'Sample tender for testing the opportunities pages. Not a real tender: do not bid.',
                'am' => 'የዕድሎች ገጾችን ለመሞከር የቀረበ ናሙና ጨረታ። እውነተኛ ጨረታ አይደለም፤ አይጫረቱ።',
                'om' => 'Caalbaasii fakkeenyaa fuulota carraawwanii qorachuuf. Caalbaasii dhugaa miti: hin dorgomina.',
            ], ['en' => "Sample: a valid trade licence\nSample: a VAT registration certificate", 'am' => "ናሙና፦ የታደሰ የንግድ ፈቃድ\nናሙና፦ የተጨማሪ እሴት ታክስ ምዝገባ ምስክር ወረቀት", 'om' => "Fakkeenya: hayyama daldalaa haaromfame\nFakkeenya: ragaa galmee VAT"]),
        ];
    }

    /**
     * @param  array<string, string>  $titles
     * @param  array<string, string>  $summaries
     * @param  array<string, string>|null  $eligibility
     * @param  array<string, string>|null  $outcomes
     * @return array<string, array<string, mixed>>
     */
    private function texts(array $titles, array $summaries, ?array $eligibility = null, ?array $outcomes = null): array
    {
        $out = [];
        foreach (['en', 'am', 'om'] as $locale) {
            $out[$locale] = [
                'title' => self::MARK.' '.$titles[$locale],
                'summary' => $summaries[$locale],
                'eligibility' => $eligibility[$locale] ?? null,
                'how_to_apply' => match ($locale) {
                    'am' => 'ናሙና፦ ምንም ማመልከቻ አይቀበልም።',
                    'om' => 'Fakkeenya: iyyannoon tokkollee hin fudhatamu.',
                    default => 'Sample: no applications are taken.',
                },
                'outcome' => $outcomes[$locale] ?? null,
                'published' => true,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function advisoryText(string $title, string $excerpt, string $affected, string $actions): array
    {
        return [
            'title' => self::MARK.' '.$title,
            'excerpt' => $excerpt,
            'affected' => $affected,
            'actions' => $actions,
            'body' => StoryText::body([$excerpt]),
            'status' => 'published',
        ];
    }

    private function remove(): int
    {
        $opportunities = Opportunity::query()->withTrashed()->whereHas('translations', fn ($q) => $q->where('title', 'like', self::MARK.'%'))->get();
        $opportunities->each(fn (Opportunity $o) => $o->forceDelete());
        $posts = Post::query()->withTrashed()->where('type', 'advisory')->whereHas('translations', fn ($q) => $q->where('title', 'like', self::MARK.'%'))->get();
        $posts->each(fn (Post $p) => $p->forceDelete());
        $campaigns = Campaign::query()->where('status', CampaignStatus::Draft->value)->where('name', 'like', '%'.self::MARK.'%')->delete();

        $this->components->info(sprintf('Removed %d opportunity(ies), %d advisory(ies), %d campaign draft(s).', $opportunities->count(), $posts->count(), $campaigns));

        return self::SUCCESS;
    }
}
