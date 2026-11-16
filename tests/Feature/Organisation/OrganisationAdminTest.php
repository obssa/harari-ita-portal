<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Content\Actions\DuplicatePost;
use App\Domain\Content\Actions\SavePost;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\Post;
use App\Domain\Organisation\Actions\SaveLeader;
use App\Domain\Organisation\Actions\SaveOffice;
use App\Domain\Organisation\Actions\SavePillar;
use App\Domain\Organisation\Enums\LeaderBody;
use App\Domain\Organisation\Enums\OfficeType;
use App\Domain\Organisation\Models\Leader;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\Pillar;
use App\Filament\Resources\Leaders\Pages\CreateLeader;
use App\Filament\Resources\Leaders\Pages\EditLeader;
use App\Filament\Resources\Offices\Pages\CreateOffice;
use App\Filament\Resources\Offices\Pages\EditOffice;
use App\Filament\Resources\Pillars\Pages\CreatePillar;
use App\Filament\Resources\Posts\Pages\EditPost;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

/**
 * @param  array<string, mixed>  $data
 */
function makeOffice(string $name, OfficeType $type = OfficeType::RegionalOffice, ?Office $parent = null, array $data = []): Office
{
    return app(SaveOffice::class)->handle(new Office, [
        'type' => $type->value,
        'parent_id' => $parent?->id,
        'translations' => ['en' => ['name' => $name, 'published' => true]],
        ...$data,
    ], staff(SystemRole::CommunicationsEditor));
}

/**
 * @param  array<string, mixed>  $data
 */
function makeLeader(string $name, LeaderBody $body = LeaderBody::Management, array $data = []): Leader
{
    return app(SaveLeader::class)->handle(new Leader, [
        'body' => $body->value,
        'translations' => ['en' => ['name' => $name, 'position' => 'Sample position', 'published' => true]],
        ...$data,
    ], staff(SystemRole::CommunicationsEditor));
}

it('keeps the office tree free of loops and within four levels', function () {
    $branch = makeOffice('Sample branch office', OfficeType::Directorate);
    $regional = makeOffice('Sample woreda', OfficeType::RegionalOffice, $branch);
    $kebele = makeOffice('Sample kebele', OfficeType::Team, $regional);
    $basic = makeOffice('Sample basic organisation', OfficeType::Partner, $kebele);

    expect($basic->ancestorIds())->toBe([$kebele->id, $regional->id, $branch->id])
        ->and(fn () => makeOffice('Too deep', OfficeType::Partner, $basic))->toThrow(ValidationException::class)
        ->and(fn () => app(SaveOffice::class)->handle($branch, [
            'type' => 'directorate',
            'parent_id' => $kebele->id,
            'translations' => ['en' => ['name' => 'Sample branch office']],
        ], staff(SystemRole::CommunicationsEditor)))->toThrow(ValidationException::class);
});

it('validates office details', function (array $data) {
    expect(fn () => makeOffice('Sample office', data: $data))->toThrow(ValidationException::class);
})->with([
    'missing type' => [['type' => 'ministry']],
    'half a map pin' => [['latitude' => '9.31']],
    'latitude out of range' => [['latitude' => '91', 'longitude' => '42.1']],
    'bad email' => [['email' => 'not-an-email']],
]);

it('stores contacts, the map pin and the head of office', function () {
    $head = makeLeader('Sample head', LeaderBody::Directors);
    $office = makeOffice('Sample sector office', OfficeType::Team, data: [
        'head_leader_id' => $head->id,
        'phone' => ' +251 25 000 0000 ',
        'email' => 'office@example.org',
        'latitude' => '9.3112345678',
        'longitude' => '42.1234567',
    ]);

    expect($office->head?->is($head))->toBeTrue()
        ->and($office->phone)->toBe('+251 25 000 0000')
        ->and($office->latitude)->toBe(9.311235)
        ->and($office->hasMap())->toBeTrue()
        ->and($office->translation('en')?->isLive())->toBeTrue();
});

it('saves leaders with terms, socials and per-language publishing', function () {
    $pillar = app(SavePillar::class)->handle(new Pillar, ['translations' => ['en' => ['name' => 'Sample youth pillar', 'published' => true]]], staff(SystemRole::CommunicationsEditor));
    $leader = makeLeader('Sample chair', LeaderBody::Pillars, [
        'pillar_id' => $pillar->id,
        'term_start' => '2024-07-01',
        'term_end' => '2029-06-30',
        'socials' => ['telegram' => 'https://t.me/sample', 'facebook' => ''],
        'translations' => [
            'en' => ['name' => 'Sample chair', 'published' => true],
            'am' => ['name' => 'ናሙና ሰብሳቢ'],
        ],
    ]);

    expect($leader->pillar?->is($pillar))->toBeTrue()
        ->and($leader->term_start?->toDateString())->toBe('2024-07-01')
        ->and($leader->socialLinks())->toBe(['telegram' => 'https://t.me/sample'])
        ->and($leader->translation('en', fallback: false)?->getAttribute('status'))->toBe(ContentStatus::Published)
        ->and($leader->translation('am', fallback: false)?->getAttribute('status'))->toBe(ContentStatus::Draft)
        ->and($leader->translation('am', fallback: false)?->slug)->toBe('namuna-sebisabi');

    expect(fn () => makeLeader('Sample', data: ['socials' => ['x' => 'http://x.com/sample']]))->toThrow(ValidationException::class)
        ->and(fn () => makeLeader('Sample', data: ['term_start' => '2025-01-01', 'term_end' => '2024-01-01']))->toThrow(ValidationException::class)
        ->and(fn () => makeLeader('Sample', data: ['translations' => ['en' => ['name' => ' ']]]))->toThrow(ValidationException::class);
});

it('lets editors and publishers manage the directory and keeps it from others', function () {
    $office = makeOffice('Sample office');

    $this->actingAs(staff(SystemRole::Author))->get('/admin/offices')->assertForbidden();
    $this->actingAs(staff(SystemRole::Translator))->get('/admin/leaders')->assertForbidden();
    $this->actingAs(staff(SystemRole::Editor))->get('/admin/pillars')->assertOk();
    $this->actingAs(staff(SystemRole::CommunicationsEditor))->get('/admin/offices/'.$office->id.'/edit')->assertOk()->assertSee('Sample office');

    makeOffice('Sample kebele', OfficeType::Team, $office);
    expect(staff(SystemRole::CommunicationsEditor)->can('delete', $office))->toBeFalse();
});

it('creates and edits offices, leaders and pillars from the admin', function () {
    $this->actingAs(staff(SystemRole::CommunicationsEditor));

    livewire(CreateOffice::class)
        ->fillForm(['type' => 'team', 'translations' => ['am' => ['name' => 'ናሙና የዘርፍ ጽሕፈት ቤት', 'hours' => 'ከሰኞ እስከ ዓርብ', 'published' => true]]])
        ->call('create')
        ->assertHasNoFormErrors();
    $office = Office::query()->with('translations')->firstOrFail();
    expect($office->type)->toBe(OfficeType::Team)
        ->and($office->translation('am', fallback: false)?->getAttribute('hours'))->toBe('ከሰኞ እስከ ዓርብ');

    livewire(CreateLeader::class)
        ->fillForm(['body' => 'management', 'office_id' => $office->id, 'translations' => ['en' => ['name' => 'Sample secretary', 'position' => 'Sample role']]])
        ->call('create')
        ->assertHasNoFormErrors();
    $leader = Leader::query()->firstOrFail();
    expect($leader->office?->is($office))->toBeTrue();

    livewire(EditLeader::class, ['record' => $leader->getRouteKey()])
        ->assertFormSet(['translations.en.position' => 'Sample role', 'translations.en.published' => false])
        ->fillForm(['translations.en.published' => true])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($leader->fresh()?->translation('en')?->isLive())->toBeTrue();

    livewire(EditOffice::class, ['record' => $office->getRouteKey()])
        ->fillForm(['head_leader_id' => $leader->id])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($office->fresh()?->head_leader_id)->toBe($leader->id);

    livewire(CreatePillar::class)
        ->fillForm(['head_leader_id' => $leader->id, 'translations' => ['om' => ['name' => 'Liigii Dargaggootaa (fakkeenya)', 'published' => true]]])
        ->call('create')
        ->assertHasNoFormErrors();
    expect(Pillar::query()->firstOrFail()->head?->is($leader))->toBeTrue();
});

it('links posts to offices, pillars and leaders and keeps the links on copies', function () {
    $office = makeOffice('Sample office');
    $pillar = app(SavePillar::class)->handle(new Pillar, ['translations' => ['en' => ['name' => 'Sample pillar']]], staff(SystemRole::CommunicationsEditor));
    $leader = makeLeader('Sample speaker');

    $post = app(SavePost::class)->handle(new Post, [
        'type' => 'speech',
        'offices' => [$office->id],
        'pillars' => [$pillar->id],
        'leaders' => [$leader->id],
        'translations' => ['en' => ['title' => 'Sample speech']],
    ], staff(SystemRole::CommunicationsEditor));

    expect($post->offices->modelKeys())->toBe([$office->id])
        ->and($leader->posts()->pluck('posts.id')->all())->toBe([$post->id])
        ->and($pillar->posts()->count())->toBe(1);

    $copy = app(DuplicatePost::class)->handle($post);
    expect($copy->leaders()->pluck('leaders.id')->all())->toBe([$leader->id]);

    $this->actingAs(staff(SystemRole::CommunicationsEditor));
    livewire(EditPost::class, ['record' => $post->getRouteKey()])
        ->assertFormSet(['offices' => [$office->id], 'leaders' => [$leader->id]]);
});
