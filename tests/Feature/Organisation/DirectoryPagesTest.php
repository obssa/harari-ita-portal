<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Content\Actions\SavePost;
use App\Domain\Content\Models\Post;
use App\Domain\Content\Workflow\ContentWorkflow;
use App\Domain\Content\Workflow\Transition;
use App\Domain\Organisation\Actions\SaveLeader;
use App\Domain\Organisation\Actions\SaveOffice;
use App\Domain\Organisation\Actions\SavePillar;
use App\Domain\Organisation\Models\Leader;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\Pillar;

/**
 * @param  array<string, array<string, mixed>>  $translations
 * @param  array<string, mixed>  $data
 */
function directoryOffice(array $translations, string $type = 'regional_office', array $data = []): Office
{
    return app(SaveOffice::class)->handle(new Office, ['type' => $type, 'translations' => $translations, ...$data], staff(SystemRole::Publisher));
}

/**
 * @param  array<string, array<string, mixed>>  $translations
 * @param  array<string, mixed>  $data
 */
function directoryLeader(array $translations, string $body = 'management', array $data = []): Leader
{
    return app(SaveLeader::class)->handle(new Leader, ['body' => $body, 'translations' => $translations, ...$data], staff(SystemRole::Publisher));
}

it('lists published leaders by body and hides drafts', function () {
    directoryLeader(['en' => ['name' => 'Sample Chair', 'position' => 'Sample head of the branch', 'published' => true]], data: ['rank' => 1]);
    directoryLeader(['en' => ['name' => 'Sample Member', 'published' => true]], 'directors');
    directoryLeader(['en' => ['name' => 'Sample Draft Person']]);

    $this->get('/en/about/leadership')
        ->assertOk()
        ->assertSeeInOrder([__('organisation.leader_body.management', [], 'en'), 'Sample Chair', __('organisation.leader_body.directors', [], 'en'), 'Sample Member'])
        ->assertDontSee('Sample Draft Person');

    $this->get('/am/about/leadership')->assertOk()->assertSee(__('directory.empty', [], 'am'));
});

it('shows a leader profile with office, term, socials, posts and person data', function () {
    $office = directoryOffice(['en' => ['name' => 'Sample Woreda Office', 'published' => true]]);
    $leader = directoryLeader(['en' => ['name' => 'Sample Speaker', 'position' => 'Sample role', 'summary' => 'Sample introduction.', 'published' => true]], 'directors', [
        'office_id' => $office->id,
        'term_start' => '2024-07-01',
        'socials' => ['telegram' => 'https://t.me/sample'],
        'email' => 'speaker@example.org',
    ]);
    $publisher = staff(SystemRole::Publisher);
    $speech = app(SavePost::class)->handle(new Post, [
        'type' => 'speech',
        'leaders' => [$leader->id],
        'translations' => ['en' => ['title' => 'Sample speech on the budget']],
    ], $publisher);
    app(ContentWorkflow::class)->apply($speech, 'en', Transition::Publish, $publisher);

    $html = (string) $this->get('/en/about/leadership/sample-speaker')
        ->assertOk()
        ->assertSee('Sample role')
        ->assertSee('/en/about/offices/sample-woreda-office')
        ->assertSee('https://t.me/sample')
        ->assertSee('mailto:speaker@example.org', escape: false)
        ->assertSee(__('leaders.present', [], 'en'))
        ->getContent();

    expect($html)->toContain('"@type":"Person"')
        ->toContain('Sample speech on the budget');

    $this->get('/am/about/leadership/sample-speaker')->assertNotFound();
});

it('lists offices with type filter and search, and shows an office page with map, head and sub-offices', function () {
    $head = directoryLeader(['en' => ['name' => 'Sample Office Head', 'published' => true]], 'directors');
    $directorate = directoryOffice(['en' => ['name' => 'Sample Directorate', 'address' => 'Sample street', 'hours' => 'Mon to Fri', 'published' => true]], 'directorate', [
        'head_leader_id' => $head->id,
        'latitude' => '9.3125',
        'longitude' => '42.1194',
        'phone' => '+251 25 000 0000',
    ]);
    directoryOffice(['en' => ['name' => 'Sample Team', 'published' => true]], 'team', ['parent_id' => $directorate->id]);
    directoryOffice(['en' => ['name' => 'Sample Unpublished Office']], 'team');

    $this->get('/en/about/offices')->assertOk()->assertSee('Sample Directorate')->assertSee('Sample Team')->assertDontSee('Sample Unpublished Office');
    $this->get('/en/about/offices?type=team')->assertOk()->assertDontSee('Sample Directorate')->assertSee('Sample Team');
    $this->get('/en/about/offices?q=directorate')->assertOk()->assertSee('Sample Directorate')->assertDontSee('Sample Team');
    $this->get('/en/about/offices?q=%25')->assertOk()->assertSee(__('directory.empty', [], 'en'));

    $this->get('/en/about/offices/sample-directorate')
        ->assertOk()
        ->assertSee('Sample Office Head')
        ->assertSee('Sample street')
        ->assertSee('tel:+251250000000', escape: false)
        ->assertSee('https://www.openstreetmap.org/export/embed.html', escape: false)
        ->assertSee('Sample Team')
        ->assertHeader('Content-Security-Policy');

    expect((string) $this->get('/en/about/offices/sample-directorate')->headers->get('Content-Security-Policy'))
        ->toContain('https://www.openstreetmap.org');
});

it('shows pillars with their chair and leadership', function () {
    $chair = directoryLeader(['en' => ['name' => 'Sample Pillar Chair', 'published' => true]], 'pillars');
    $pillar = app(SavePillar::class)->handle(new Pillar, [
        'head_leader_id' => $chair->id,
        'translations' => ['en' => ['name' => 'Sample Youth Pillar', 'summary' => 'Sample summary', 'published' => true]],
    ], staff(SystemRole::Publisher));
    directoryLeader(['en' => ['name' => 'Sample Pillar Secretary', 'published' => true]], 'pillars', ['pillar_id' => $pillar->id]);

    $this->get('/en/pillars')->assertOk()->assertSee('Sample Youth Pillar')->assertSee('Sample summary')
        ->assertSee(__('pillars.title', [], 'en'));
    $this->get('/en/pillars/sample-youth-pillar')
        ->assertOk()
        ->assertSee('Sample Pillar Chair')
        ->assertSee('Sample Pillar Secretary');
});

it('builds the organisational structure from the office tree', function () {
    $head = directoryLeader(['en' => ['name' => 'Sample Structure Head', 'published' => true]], 'directors');
    $directorate = directoryOffice(['en' => ['name' => 'Sample Root Office', 'published' => true]], 'directorate', ['head_leader_id' => $head->id]);
    $regional = directoryOffice(['en' => ['name' => 'Sample Regional Office', 'published' => true]], 'regional_office', ['parent_id' => $directorate->id]);
    directoryOffice(['en' => ['name' => 'Sample Team Unit', 'published' => true]], 'team', ['parent_id' => $regional->id]);

    $this->get('/en/about/structure')
        ->assertOk()
        ->assertSeeInOrder(['Sample Root Office', __('structure.headed_by', ['name' => 'Sample Structure Head'], 'en'), 'Sample Regional Office', 'Sample Team Unit'])
        ->assertSee('<details', escape: false);
});

it('shows Amharic directory entries to Harari readers and links each language', function () {
    directoryLeader([
        'am' => ['name' => 'ናሙና መሪ', 'published' => true],
        'en' => ['name' => 'Sample Leader', 'published' => true],
    ]);

    $this->get('/har/about/leadership')->assertOk()->assertSee('ናሙና መሪ')->assertSee('lang="am"', escape: false);
    $this->get('/en/about/leadership/sample-leader')
        ->assertOk()
        ->assertSee('hreflang="am"', escape: false)
        ->assertSee(url('/om/about/leadership'), escape: false);
});

it('lists the directory in the sitemaps and keeps pages off its addresses', function () {
    directoryOffice(['en' => ['name' => 'Sample Mapped Office', 'published' => true]]);
    directoryLeader(['en' => ['name' => 'Sample Listed Leader', 'published' => true]]);

    $this->get('/sitemaps/en.xml')
        ->assertOk()
        ->assertSee(url('/en/about/offices/sample-mapped-office'), escape: false)
        ->assertSee(url('/en/about/leadership/sample-listed-leader'), escape: false)
        ->assertSee('<loc>'.url('/en/about/structure').'</loc>', escape: false);
});
