<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Content\Actions\LoadContentPack;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\ContentPackItem;
use App\Domain\Content\Models\Post;
use App\Domain\Events\Models\Event;
use App\Domain\Homepage\Enums\SectionKey;
use App\Domain\Homepage\Models\HeroSlide;
use App\Domain\Homepage\Models\HomepageSection;
use App\Domain\Import\Models\ContentImport;
use App\Domain\Import\Models\ContentSource;
use App\Domain\Media\Actions\UploadMedia;
use App\Domain\Organisation\Actions\SaveLeader;
use App\Domain\Organisation\Enums\LeaderBody;
use App\Domain\Organisation\Models\Leader;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Social\Enums\Platform;
use App\Domain\Social\Models\SocialSource;
use App\Settings\SiteSettings;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

/**
 * A pack file with the given records, in a folder of its own.
 *
 * @param  array<string, mixed>  $records
 */
function samplePack(array $records): string
{
    $folder = storage_path('framework/testing/pack-'.uniqid());
    File::ensureDirectoryExists($folder);
    file_put_contents("{$folder}/pack.json", json_encode(['version' => 1, ...$records]));

    return "{$folder}/pack.json";
}

it('ships the Agency\'s first content in three languages, loaded once, with no sample labels', function () {
    staff(SystemRole::SuperAdmin, mfa: false);

    $counts = app(LoadContentPack::class)->handle(database_path('content/pack.json'));
    expect($counts)->toMatchArray(['pillars' => 5, 'pages' => 3, 'posts' => 4, 'advisories' => 3, 'courses' => 6, 'programmes' => 2, 'projects' => 3, 'leaders' => 1, 'homepage' => 1])
        ->and(app(LoadContentPack::class)->handle(database_path('content/pack.json')))->toBe([])
        ->and(Pillar::query()->count())->toBe(5)
        ->and(Post::query()->where('type', 'advisory')->count())->toBe(3);
    foreach (['en', 'am', 'om'] as $locale) {
        expect(Pillar::query()->firstOrFail()->translation($locale, fallback: false)?->status)->toBe(ContentStatus::Published);
    }

    // ADR-0045, ADR-0046: the homepage leads with the pillars and shows the programmes and courses while nothing has
    // dates yet; the heritage band closes it. Nothing is labelled as a sample.
    $home = (string) $this->get('/en')->assertOk()->getContent();
    expect($home)->toContain('Digital Government')->toContain('Harar Digital Innovation Programme')
        ->toContain('Digital literacy for public servants')->toContain(__('homepage.heritage.title', [], 'en'))
        ->toContain('/en/digital-ethiopia-2030')->not->toContain('Sample');
    $this->get('/am')->assertOk()->assertSee('ዲጂታል መንግሥት');
    $this->get('/en/harar')->assertOk()->assertSee('whc.unesco.org/en/list/1189', escape: false);
    $this->get('/en/news')->assertOk()->assertSee('Mesob');
    $this->get('/en/advisories')->assertOk()->assertSee('two-step sign-in');

    // ADR-0049, ADR-0050: the head of the Agency, as state media published the post, with the source, its date and
    // its photo.
    $head = Leader::query()->sole();
    expect($head->body)->toBe(LeaderBody::Management)->and($head->source_url)->toStartWith('https://www.ena.et/')
        ->and($head->source_date?->toDateString())->toBe('2026-11-09')->and($head->portrait?->credit)->toBe('Ethiopian News Agency (ENA)');
    foreach (['en', 'am', 'om'] as $locale) {
        expect($head->translation($locale, fallback: false)?->status)->toBe(ContentStatus::Published);
    }
    $this->get('/en/about/leadership')->assertOk()->assertSee(__('leaders.featured', [], 'en'));
    $this->get('/om/about/leadership/'.$head->translation('om', fallback: false)?->slug)->assertOk()->assertSee('ena.et/web/amh/w/amh_9899183');
    expect(json_encode(json_decode((string) file_get_contents(database_path('content/pack.json')), true)))->not->toContain('Sample');
});

it('loads a pack, published, with a portrait, heads, pages, the homepage and a slide', function () {
    staff(SystemRole::SuperAdmin, mfa: false);
    $path = samplePack([
        'pillars' => [['key' => 'pillar.sample', 'head' => 'leader.sample-head', 'translations' => ['en' => ['name' => 'Sample Pillar', 'summary' => 'Sample summary.']]]],
        'offices' => [
            ['key' => 'office.sample', 'type' => 'directorate', 'head' => 'leader.sample-head', 'translations' => ['en' => ['name' => 'Sample Directorate']]],
            ['key' => 'office.sample-team', 'type' => 'team', 'parent' => 'office.sample', 'translations' => ['en' => ['name' => 'Sample Team']]],
        ],
        'leaders' => [['key' => 'leader.sample-head', 'body' => 'management', 'rank' => 1, 'office' => 'office.sample', 'photo' => 'sample-head.jpg',
            'source_url' => 'https://example.org/sample-appointment',
            'translations' => ['en' => ['name' => 'Sample Head', 'position' => 'Sample head of the Agency'], 'am' => ['name' => 'ናሙና ኃላፊ']]]],
        'pages' => [['key' => 'page.about', 'translations' => ['en' => ['title' => 'Sample about page', 'slug' => 'about',
            'body' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Sample text about the Agency.']]]]]]]]],
        'events' => [['key' => 'event.sample', 'starts_at' => now()->addMonth()->toDateString(), 'all_day' => true, 'translations' => ['en' => ['title' => 'Sample Event']]]],
        'homepage' => [
            'leadership' => ['leader' => 'leader.sample-head', 'quote' => ['en' => 'Sample quote.']],
            'counters' => [['value' => '1', 'label' => ['en' => 'Sample figure']], ['value' => '2', 'label' => ['en' => 'Another sample figure']]],
        ],
        'settings' => ['social_links' => ['telegram' => 'https://t.me/sample_channel']],
        'slides' => [['key' => 'slide.sample', 'name' => 'Sample slide', 'layout' => 'photo', 'sort' => 1,
            'content' => ['en' => ['title' => 'Sample slide heading']],
            'buttons' => [['link' => 'route:volunteer.create', 'style' => 'primary', 'label' => ['en' => 'Sample button']]]]],
    ]);
    File::ensureDirectoryExists(dirname($path).'/photos');
    $image = imagecreatetruecolor(600, 600);
    imagefill($image, 0, 0, imagecolorallocate($image, 5, 37, 59));
    imagejpeg($image, dirname($path).'/photos/sample-head.jpg', 80);

    $counts = app(LoadContentPack::class)->handle($path);

    expect($counts)->toMatchArray(['pillars' => 1, 'offices' => 2, 'leaders' => 1, 'pages' => 1, 'events' => 1, 'homepage' => 2, 'settings' => 1, 'slides' => 1]);
    $head = Leader::query()->firstOrFail();
    expect($head->body)->toBe(LeaderBody::Management)
        ->and($head->translation('am', fallback: false)?->name)->toBe('ናሙና ኃላፊ')
        ->and($head->translation('am', fallback: false)?->status)->toBe(ContentStatus::Published)
        ->and($head->portrait?->alt)->toHaveKey('en', 'Sample Head')
        ->and($head->portrait?->credit)->toBe('example.org')
        ->and(Office::query()->where('type', 'directorate')->value('head_leader_id'))->toBe($head->id)
        ->and(Office::query()->where('type', 'team')->value('parent_id'))->toBe(Office::query()->where('type', 'directorate')->value('id'))
        ->and(Pillar::query()->value('head_leader_id'))->toBe($head->id)
        ->and(Event::query()->count())->toBe(1)
        ->and(HomepageSection::query()->where('key', SectionKey::Leadership->value)->firstOrFail()->setting('leader_id'))->toBe($head->id)
        ->and(HomepageSection::query()->where('key', SectionKey::Counters->value)->firstOrFail()->setting('items'))->toHaveCount(2)
        ->and(app(SiteSettings::class)->social_links['telegram'] ?? null)->toBe('https://t.me/sample_channel')
        ->and(HeroSlide::query()->count())->toBe(1);

    $this->get('/en/about')->assertOk()->assertSee('Sample text about the Agency.');
    $this->get('/en/about/leadership')->assertOk()->assertSee('Sample Head')->assertSee(LeaderBody::Management->label('en'));
    $this->get('/en/about/structure')->assertOk()->assertSeeInOrder(['Sample Directorate', 'Sample Team']);
    $this->get('/en')->assertOk()->assertSee('Sample slide heading');
    expect(app(LoadContentPack::class)->handle($path))->toBe([]);
});

it('loads each record once and never brings back what an editor deleted', function () {
    staff(SystemRole::SuperAdmin, mfa: false);
    $path = samplePack(['pillars' => [
        ['key' => 'pillar.sample', 'translations' => ['en' => ['name' => 'Sample Wing', 'summary' => 'Sample summary.']]],
    ]]);

    expect(app(LoadContentPack::class)->handle($path))->toBe(['pillars' => 1]);
    Pillar::query()->firstOrFail()->delete();

    expect(app(LoadContentPack::class)->handle($path))->toBe([])
        ->and(Pillar::query()->count())->toBe(0)
        ->and(ContentPackItem::query()->where('key', 'pillar.sample')->exists())->toBeTrue();
});

it('keeps the social links and homepage an editor already set', function () {
    staff(SystemRole::SuperAdmin, mfa: false);
    $settings = app(SiteSettings::class);
    $settings->social_links = ['telegram' => 'https://t.me/sample_channel'];
    $settings->save();

    app(LoadContentPack::class)->handle(samplePack(['settings' => ['social_links' => ['telegram' => 'https://t.me/sample_agency', 'facebook' => 'https://facebook.com/sample']]]));

    expect(app(SiteSettings::class)->social_links)->toBe(['telegram' => 'https://t.me/sample_channel', 'facebook' => 'https://facebook.com/sample']);
});

it('adds the Agency\'s channel to the social hub once and never brings it back after an editor removed it', function () {
    staff(SystemRole::SuperAdmin, mfa: false);
    $path = samplePack(['social_sources' => [
        ['key' => 'social.telegram.sample', 'platform' => 'telegram', 'handle' => 'sample_channel', 'name' => 'Sample channel', 'interval_minutes' => 5],
        ['key' => 'social.sample.unknown', 'platform' => 'sample-network', 'handle' => 'sample'],
    ]]);

    expect(app(LoadContentPack::class)->handle($path))->toBe(['social_sources' => 1]);
    $source = SocialSource::query()->sole();
    expect($source->platform)->toBe(Platform::Telegram)
        ->and($source->name)->toBe('Sample channel')
        ->and($source->interval_minutes)->toBe(15)
        ->and($source->enabled)->toBeTrue();

    $source->delete();

    expect(app(LoadContentPack::class)->handle($path))->toBe([])
        ->and(SocialSource::query()->count())->toBe(0);
});

it('leaves a channel an editor already added to the social hub', function () {
    staff(SystemRole::SuperAdmin, mfa: false);
    SocialSource::query()->create(['platform' => Platform::Telegram, 'handle' => 'sample_channel', 'name' => 'Added by an editor']);

    expect(app(LoadContentPack::class)->handle(samplePack(['social_sources' => [
        ['key' => 'social.telegram.sample', 'platform' => 'telegram', 'handle' => 'sample_channel', 'name' => 'Sample channel'],
    ]])))->toBe([])
        ->and(SocialSource::query()->sole()->name)->toBe('Added by an editor');
});

it('corrects a name and a phrase once, as the Agency asked, and then leaves them to editors', function () {
    staff(SystemRole::SuperAdmin, mfa: false);
    $body = fn (string $text): array => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]]]];
    app(LoadContentPack::class)->handle(samplePack([
        'leaders' => [['key' => 'leader.sample', 'body' => 'management', 'translations' => ['en' => ['name' => 'Sample Person', 'position' => 'Sample head']]]],
        'pages' => [['key' => 'page.sample', 'translations' => ['en' => ['title' => 'Sample page', 'slug' => 'sample-page', 'body' => $body('It is headed by Sample Person.')]]]],
    ]));
    $corrections = samplePack(['updates' => [
        ['key' => 'leader.sample#name', 'target' => 'leader.sample', 'rename' => ['en' => ['name' => 'H.E Sample Person Full', 'position' => 'Head of the sample agency']]],
        ['key' => 'page.sample#name', 'target' => 'page.sample', 'replace' => ['en' => [['Sample Person', 'H.E Sample Person Full']]]],
    ]]);

    expect(app(LoadContentPack::class)->handle($corrections))->toBe(['updates' => 2]);
    $leader = Leader::query()->firstOrFail();
    expect($leader->translation('en')?->name)->toBe('H.E Sample Person Full')
        ->and($leader->translation('en')?->position)->toBe('Head of the sample agency')
        ->and($leader->translation('en')?->slug)->toBe('sample-person');
    $this->get('/en/sample-page')->assertOk()->assertSee('It is headed by H.E Sample Person Full.');

    $leader->translation('en')?->forceFill(['name' => 'Name an editor chose'])->save();
    expect(app(LoadContentPack::class)->handle($corrections))->toBe([])
        ->and($leader->fresh()?->translation('en')?->name)->toBe('Name an editor chose');
});

it('adds a credited portrait once to a leader the pack loaded without one', function () {
    staff(SystemRole::SuperAdmin, mfa: false);
    app(LoadContentPack::class)->handle(samplePack([
        'leaders' => [['key' => 'leader.sample', 'body' => 'management', 'translations' => ['en' => ['name' => 'Sample Person'], 'am' => ['name' => 'ናሙና ሰው']]]],
    ]));
    $path = samplePack(['updates' => [['key' => 'leader.sample#portrait', 'target' => 'leader.sample', 'portrait' => 'sample.jpg',
        'portrait_credit' => 'Sample News Agency', 'portrait_source' => 'https://example.org/sample-report']]]);
    File::ensureDirectoryExists(dirname($path).'/photos');
    $image = imagecreatetruecolor(400, 400);
    imagejpeg($image, dirname($path).'/photos/sample.jpg', 80);

    expect(app(LoadContentPack::class)->handle($path))->toBe(['updates' => 1]);
    $leader = Leader::query()->sole();
    expect($leader->portrait)->not->toBeNull()
        ->and($leader->portrait?->credit)->toBe('Sample News Agency')
        ->and($leader->portrait?->altFor('am'))->toBe('ናሙና ሰው');

    // ADR-0050: once only; a portrait an editor removes is not put back.
    $leader->forceFill(['portrait_id' => null])->save();
    expect(app(LoadContentPack::class)->handle($path))->toBe([])->and($leader->fresh()?->portrait_id)->toBeNull();
});

it('corrects a phrase once wherever the site shows it, then leaves it to editors', function () {
    $admin = staff(SystemRole::SuperAdmin, mfa: false);
    $body = fn (string $text): array => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]]]];
    app(LoadContentPack::class)->handle(samplePack([
        'leaders' => [['key' => 'leader.sample', 'body' => 'management', 'translations' => ['en' => ['name' => 'H.E Sample Wayyesa', 'position' => 'Sample head']]]],
        'pages' => [['key' => 'page.sample', 'translations' => ['en' => ['title' => 'Sample page', 'slug' => 'sample-page', 'body' => $body('Led by H.E Sample Wayyesa.')]]]],
        'slides' => [['key' => 'slide.sample', 'name' => 'Message from H.E Sample Wayyesa', 'layout' => 'photo', 'content' => ['en' => ['title' => 'Sample heading', 'note' => "H.E Sample Wayyesa\nSample head"]]]],
    ]));
    $path = storage_path('framework/testing/pack-picture-'.uniqid().'.jpg');
    @mkdir(dirname($path), 0777, true);
    $image = imagecreatetruecolor(640, 480);
    imagefill($image, 0, 0, imagecolorallocate($image, 5, 37, 59));
    imagejpeg($image, $path, 80);
    $picture = app(UploadMedia::class)->handle($path, 'sample-portrait.jpg', $admin);
    $picture->forceFill(['alt' => ['en' => 'Portrait of H.E Sample Wayyesa', 'am' => 'Sample Amharic text']])->save();
    $correction = samplePack(['corrections' => [
        ['key' => 'correction.sample', 'replace' => [['H.E Sample Wayyesa', 'H.E Sample Weyessa']]],
    ]]);

    expect(app(LoadContentPack::class)->handle($correction))->toBe(['corrections' => 1]);
    expect(Leader::query()->firstOrFail()->translation('en')?->name)->toBe('H.E Sample Weyessa')
        ->and(HeroSlide::query()->firstOrFail()->name)->toBe('Message from H.E Sample Weyessa')
        ->and(HeroSlide::query()->firstOrFail()->text('note', 'en')[0])->toBe("H.E Sample Weyessa\nSample head")
        ->and($picture->fresh()?->alt)->toEqual(['en' => 'Portrait of H.E Sample Weyessa', 'am' => 'Sample Amharic text']);
    $this->get('/en/sample-page')->assertOk()->assertSee('Led by H.E Sample Weyessa.');

    Leader::query()->firstOrFail()->translation('en')?->forceFill(['name' => 'H.E Sample Wayyesa'])->save();
    expect(app(LoadContentPack::class)->handle($correction))->toBe([])
        ->and(Leader::query()->firstOrFail()->translation('en')?->name)->toBe('H.E Sample Wayyesa');
});

it('publishes the stories imported from the Agency\'s channel before it was trusted, once, with their dates', function () {
    staff(SystemRole::SuperAdmin, mfa: false);
    sampleImportSources();
    $source = ContentSource::query()->where('handle', 'sample_agency')->firstOrFail();
    $post = new Post(['type' => 'news', 'source_locale' => 'am']);
    $post->forceFill(['source_url' => 'https://t.me/sample_agency/1', 'source_name' => $source->name])->save();
    $post->putTranslation('am', ['title' => 'የናሙና ዜና', 'status' => ContentStatus::Draft]);
    ContentImport::query()->create(['content_source_id' => $source->id, 'external_id' => 'sample_agency/1', 'message_at' => '2026-10-01 09:00:00', 'status' => ContentImport::IMPORTED, 'post_id' => $post->id]);

    expect(app(LoadContentPack::class)->handle(samplePack([])))->toBe(['stories' => 1]);
    $translation = $post->fresh()?->translation('am', fallback: false);
    expect($translation?->status)->toBe(ContentStatus::Published)
        ->and($translation?->published_at?->toDateTimeString())->toBe('2026-10-01 09:00:00');

    $translation?->forceFill(['status' => ContentStatus::Draft])->save();
    expect(app(LoadContentPack::class)->handle(samplePack([])))->toBe([])
        ->and($post->fresh()?->translation('am', fallback: false)?->status)->toBe(ContentStatus::Draft);
});

it('waits for a Super Admin and a pack file without failing a deploy', function () {
    $this->artisan('content:load-pack')->expectsOutputToContain('No Super Admin yet')->assertSuccessful();

    staff(SystemRole::SuperAdmin, mfa: false);
    $this->artisan('content:load-pack', ['--path' => storage_path('framework/testing/none.json')])->expectsOutputToContain('No content pack')->assertSuccessful();
    $this->artisan('content:load-pack', ['--path' => samplePack([])])->expectsOutputToContain('nothing new')->assertSuccessful();
});

it('accepts only web addresses as a leader source', function () {
    $publisher = staff(SystemRole::CommunicationsEditor, mfa: false);
    $data = ['body' => 'directors', 'source_url' => 'javascript:alert(1)', 'translations' => ['en' => ['name' => 'Sample Person']]];

    expect(fn () => app(SaveLeader::class)->handle(new Leader, $data, $publisher))->toThrow(ValidationException::class);

    $leader = app(SaveLeader::class)->handle(new Leader, [...$data, 'source_url' => 'https://example.org/sample', 'source_date' => '2026-10-15'], $publisher);
    expect($leader->source_url)->toBe('https://example.org/sample')
        ->and($leader->source_date?->toDateString())->toBe('2026-10-15');
});

it('lists the about page and its pages in the default menu once they are published', function () {
    staff(SystemRole::SuperAdmin, mfa: false);
    app(LoadContentPack::class)->handle(samplePack(['pages' => [
        ['key' => 'page.about', 'translations' => ['en' => ['title' => 'About the sample branch', 'slug' => 'about', 'excerpt' => 'Sample excerpt.']]],
        ['key' => 'page.history', 'parent' => 'page.about', 'translations' => ['en' => ['title' => 'Sample history', 'slug' => 'history']]],
    ]]));

    $html = (string) $this->get('/en/news')->getContent();
    expect($html)->toContain('About the sample branch', 'Sample history', '/en/about/history');
});
