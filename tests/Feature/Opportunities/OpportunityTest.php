<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Api\Actions\IssueApiKey;
use App\Domain\Api\Enums\ApiScope;
use App\Domain\Content\Models\Post;
use App\Domain\Documents\Actions\SaveDocument;
use App\Domain\Documents\Models\Document;
use App\Domain\Engagement\Models\Campaign;
use App\Domain\Innovation\Queries\OpenCalls;
use App\Domain\Opportunities\Actions\PublishAddendum;
use App\Domain\Opportunities\Actions\SaveOpportunity;
use App\Domain\Opportunities\Enums\AddendumKind;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Opportunities\Models\OpportunityAddendum;
use App\Filament\Resources\Opportunities\OpportunityResource;
use App\Filament\Resources\Opportunities\Pages\CreateOpportunity;
use App\Filament\Resources\Opportunities\Pages\EditOpportunity;
use App\Filament\Resources\Opportunities\Pages\ListOpportunities;
use App\Filament\Resources\Opportunities\RelationManagers\AddendaRelationManager;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Livewire\livewire;

/**
 * A published sample opportunity saved by a Procurement Officer.
 *
 * @param  array<string, mixed>  $data
 */
function sampleOpportunity(string $title = 'Sample network tender', array $data = [], ?User $actor = null): Opportunity
{
    return app(SaveOpportunity::class)->handle(new Opportunity, [
        'type' => 'tender', 'reference' => 'SAMPLE/T/9',
        'closes_at' => CarbonImmutable::now()->addDays(14)->toDateTimeString(),
        'translations' => ['en' => [
            'title' => $title, 'summary' => 'A sample opportunity for the tests.',
            'eligibility' => "Sample requirement one\nSample requirement two", 'how_to_apply' => 'Sample: in person.', 'published' => true,
        ]],
        ...$data,
    ], $actor ?? staff(SystemRole::ProcurementOfficer));
}

it('saves an opportunity with its rules: dates, apply address, job fields and who may change it', function () {
    $officer = staff(SystemRole::ProcurementOfficer);
    $tender = sampleOpportunity(data: ['positions' => 3, 'employment_type' => 'full_time', 'apply_email' => 'Tenders@Example.org'], actor: $officer);
    expect($tender->positions)->toBeNull()->and($tender->employment_type)->toBeNull()
        ->and($tender->apply_email)->toBe('tenders@example.org')
        ->and($tender->state())->toBe(Opportunity::STATE_OPEN);

    $vacancy = sampleOpportunity('Sample vacancy', ['type' => 'vacancy', 'positions' => 2, 'employment_type' => 'contract'], $officer);
    expect($vacancy->positions)->toBe(2)->and($vacancy->employment_type?->value)->toBe('contract');

    $save = fn (array $data) => app(SaveOpportunity::class)->handle(new Opportunity, [
        'type' => 'call', 'translations' => ['en' => ['title' => 'Sample bad call', 'published' => true]], ...$data,
    ], $officer);
    expect(fn () => $save([]))->toThrow(ValidationException::class);
    expect(fn () => $save(['opens_at' => now()->addDays(3)->toDateTimeString(), 'closes_at' => now()->addDay()->toDateTimeString()]))->toThrow(ValidationException::class);
    expect(fn () => $save(['closes_at' => now()->addDay()->toDateTimeString(), 'apply_url' => 'http://example.org/apply']))->toThrow(ValidationException::class);
    expect(fn () => $save(['closes_at' => now()->addDay()->toDateTimeString(), 'apply_email' => 'not-an-address']))->toThrow(ValidationException::class);
    expect(fn () => app(SaveOpportunity::class)->handle(new Opportunity, ['type' => 'tender', 'closes_at' => now()->addDays(3)->toDateTimeString(), 'bid_opening_at' => now()->addDays(2)->toDateTimeString(),
        'translations' => ['en' => ['title' => 'Sample early bids', 'published' => true]]], $officer))->toThrow(ValidationException::class);

    expect(fn () => sampleOpportunity('Sample other', actor: staff(SystemRole::Editor)))->toThrow(HttpException::class);
    expect(staff(SystemRole::Editor)->can('create', Opportunity::class))->toBeFalse()
        ->and($officer->can('update', $tender))->toBeTrue();
});

it('keeps a numbered log: addenda, clarifications, an extension that keeps the first deadline, and a cancellation', function () {
    $officer = staff(SystemRole::ProcurementOfficer);
    $tender = sampleOpportunity(data: ['bid_opening_at' => now()->addDays(14)->addMinutes(30)->toDateTimeString()], actor: $officer);
    $first = $tender->closes_at;
    $bids = $tender->bid_opening_at;
    $log = app(PublishAddendum::class);

    $one = $log->handle($tender, ['kind' => 'addendum', 'titles' => ['en' => 'Sample change to lot 2']], $officer);
    expect(fn () => $log->handle($tender, ['kind' => 'clarification', 'titles' => ['en' => 'Sample question?']], $officer))->toThrow(ValidationException::class);
    $two = $log->handle($tender, ['kind' => 'clarification', 'titles' => ['en' => 'Sample question?'], 'bodies' => ['en' => 'Sample answer.']], $officer);
    expect(fn () => $log->handle($tender, ['kind' => 'extension', 'titles' => ['en' => 'Sample earlier'], 'new_closes_at' => $first->subDay()->toDateTimeString()], $officer))->toThrow(ValidationException::class);
    $three = $log->handle($tender, ['kind' => 'extension', 'titles' => ['en' => 'Sample extension'], 'new_closes_at' => $first->addWeek()->toDateTimeString()], $officer);

    $tender->refresh();
    expect([$one->number, $two->number, $three->number])->toBe([1, 2, 3])
        ->and($tender->closes_at->equalTo($first->addWeek()))->toBeTrue()
        ->and($tender->original_closes_at?->equalTo($first))->toBeTrue()
        ->and($three->previous_closes_at?->equalTo($first))->toBeTrue()
        ->and($tender->wasExtended())->toBeTrue()
        // The bid opening moves with the deadline, so bids are never opened before it.
        ->and($tender->bid_opening_at?->equalTo($bids?->addWeek()))->toBeTrue();

    // Once the log has entries, the deadline changes only through an extension.
    expect(fn () => app(SaveOpportunity::class)->handle($tender, ['type' => 'tender', 'closes_at' => $first->addMonth()->toDateTimeString(),
        'translations' => ['en' => ['title' => 'Sample network tender', 'published' => true]]], $officer))->toThrow(ValidationException::class);

    // A correction keeps the number and date and shows when it was corrected.
    $log->correct($one, ['titles' => ['en' => 'Sample change to lot 2 (corrected)']], $officer);
    expect($one->refresh()->corrected_at)->not->toBeNull()->and($one->number)->toBe(1)->and($officer->can('delete', $one))->toBeFalse();

    $log->handle($tender, ['kind' => 'cancellation', 'titles' => ['en' => 'Sample cancellation'], 'bodies' => ['en' => 'Sample reason.']], $officer);
    expect($tender->refresh()->state())->toBe(Opportunity::STATE_CANCELLED)->and($tender->archived_at)->not->toBeNull();
    expect(fn () => $log->handle($tender, ['kind' => 'addendum', 'titles' => ['en' => 'Sample late']], $officer))->toThrow(ValidationException::class);
    expect(OpportunityAddendum::query()->where('opportunity_id', $tender->id)->count())->toBe(4);
});

it('works out the state from the dates and archives closed opportunities automatically', function () {
    $tender = sampleOpportunity(data: ['closes_at' => now()->addMinutes(30)->toDateTimeString()]);
    $later = sampleOpportunity('Sample later call', ['type' => 'call', 'opens_at' => now()->addDays(2)->toDateTimeString(), 'closes_at' => now()->addDays(20)->toDateTimeString()]);
    expect($tender->state())->toBe(Opportunity::STATE_CLOSING)->and($later->state())->toBe(Opportunity::STATE_UPCOMING);

    $this->get('/en/opportunities')->assertOk()->assertSeeInOrder(['Sample network tender', __('opportunities.archive', [], 'en')]);

    $this->travel(31)->minutes();
    expect($tender->refresh()->state())->toBe(Opportunity::STATE_CLOSED)->and($tender->archived_at)->toBeNull();
    $this->artisan('opportunities:archive')->assertSuccessful();
    expect($tender->refresh()->archived_at)->not->toBeNull()->and($later->refresh()->archived_at)->toBeNull();

    $page = (string) $this->get('/en/opportunities')->assertOk()->getContent();
    expect(strpos($page, 'Sample network tender'))->toBeGreaterThan(strpos($page, __('opportunities.archive', [], 'en')));
    $this->get('/en/opportunities/sample-network-tender')->assertOk()->assertSee(__('opportunities.closed_title', [], 'en'));
});

it('shows an opportunity with its documents, log, deadline file, feed and JobPosting for vacancies', function () {
    $officer = staff(SystemRole::ProcurementOfficer);
    $public = app(SaveDocument::class)->handle(new Document, ['type' => 'tender', 'translations' => ['en' => ['title' => 'Sample bid document', 'published' => true]]], $officer);
    $private = app(SaveDocument::class)->handle(new Document, ['type' => 'tender', 'translations' => ['en' => ['title' => 'Sample internal note', 'published' => true]]], $officer);
    $private->forceFill(['access' => 'internal'])->save();
    $tender = sampleOpportunity(data: ['documents' => [$public->id, $private->id], 'bid_opening_at' => now()->addDays(14)->addHour()->toDateTimeString()], actor: $officer);
    app(PublishAddendum::class)->handle($tender, ['kind' => 'clarification', 'titles' => ['en' => 'Sample may we bid jointly?'], 'bodies' => ['en' => 'Sample: yes.']], $officer);

    $this->get('/en/opportunities/sample-network-tender')
        ->assertOk()
        ->assertSee('Sample requirement two')
        ->assertSee('Sample bid document')
        ->assertDontSee('Sample internal note')
        ->assertSee('Sample may we bid jointly?')
        ->assertSee(AddendumKind::Clarification->label('en').' 1')
        ->assertSee('SAMPLE/T/9')
        ->assertDontSee('"@type":"JobPosting"', escape: false)
        ->assertSee('"@type":"BreadcrumbList"', escape: false);

    $ics = $this->get('/en/opportunities/sample-network-tender.ics')->assertOk();
    expect($ics->headers->get('Content-Type'))->toContain('text/calendar')->and(substr_count($ics->getContent(), 'BEGIN:VEVENT'))->toBe(2);

    $this->get('/en/opportunities/feed.xml')->assertOk()->assertSee('Sample network tender')->assertSee('Sample may we bid jointly?');

    sampleOpportunity('Sample junior administrator', ['type' => 'vacancy', 'positions' => 2, 'employment_type' => 'full_time'], $officer);
    $this->get('/en/opportunities/sample-junior-administrator')->assertOk()
        ->assertSee('"@type":"JobPosting"', escape: false)
        ->assertSee('"employmentType":"FULL_TIME"', escape: false)
        ->assertSee('"totalJobOpenings":2', escape: false);

    $this->get('/en/opportunities?type=vacancy')->assertOk()->assertSee('Sample junior administrator')->assertDontSee('Sample network tender');
    $this->get('/en/notices')->assertRedirect('/en/opportunities')->assertStatus(301);

    // A language that is not published is not shown.
    app(SaveOpportunity::class)->handle(new Opportunity, ['type' => 'call', 'closes_at' => now()->addWeek()->toDateTimeString(),
        'translations' => ['en' => ['title' => 'Sample draft call', 'published' => false]]], $officer);
    $this->get('/en/opportunities')->assertDontSee('Sample draft call');
    $this->get('/en/opportunities/sample-draft-call')->assertNotFound();
});

it('puts open calls and tenders on the home page, in search, the sitemap and the API', function () {
    $officer = staff(SystemRole::ProcurementOfficer);
    sampleOpportunity('Sample open call', ['type' => 'call'], $officer);
    sampleOpportunity('Sample vacancy', ['type' => 'vacancy'], $officer);

    $calls = OpenCalls::for('en');
    expect(collect($calls)->pluck('title')->all())->toContain('Sample open call')->not->toContain('Sample vacancy');

    $this->get('/en/search?q=open+call')->assertOk()->assertSee('Sample open call');
    $this->get('/sitemaps/en.xml')->assertOk()->assertSee('/en/opportunities/sample-open-call', escape: false);

    [, $key] = app(IssueApiKey::class)->handle(['name' => 'Sample app (test)', 'scopes' => [ApiScope::Opportunities->value]], staff(SystemRole::SuperAdmin));
    $this->getJson('/api/v1/opportunities?type=call', ['X-Api-Key' => $key])->assertOk()
        ->assertJsonPath('data.0.title', 'Sample open call')->assertJsonPath('data.0.state', 'open');
    $this->getJson('/api/v1/opportunities/sample-open-call', ['X-Api-Key' => $key])->assertOk()->assertJsonPath('data.addenda', []);
    $this->getJson('/api/v1/opportunities?type=lottery', ['X-Api-Key' => $key])->assertStatus(422);
});

it('lets a procurement officer create an opportunity and publish a log entry in the admin', function () {
    $officer = staff(SystemRole::ProcurementOfficer);
    $this->actingAs($officer);

    livewire(CreateOpportunity::class)
        ->fillForm(['type' => 'tender', 'reference' => 'SAMPLE/T/77', 'closes_at' => now()->addDays(10)->format('Y-m-d H:i'),
            'translations' => ['en' => ['title' => 'Sample admin tender', 'published' => true]]])
        ->call('create')
        ->assertHasNoFormErrors();
    $tender = Opportunity::query()->where('reference', 'SAMPLE/T/77')->firstOrFail();
    livewire(ListOpportunities::class)->assertCanSeeTableRecords([$tender]);
    livewire(EditOpportunity::class, ['record' => $tender->getRouteKey()])->assertOk()->assertFormSet(['reference' => 'SAMPLE/T/77']);

    livewire(AddendaRelationManager::class, ['ownerRecord' => $tender, 'pageClass' => EditOpportunity::class])
        ->callTableAction('publish', data: ['kind' => 'addendum', 'titles' => ['en' => 'Sample admin addendum']])
        ->assertHasNoTableActionErrors();
    expect($tender->addenda()->count())->toBe(1);

    $this->actingAs(staff(SystemRole::Editor, mfa: false))->get(OpportunityResource::getUrl())->assertForbidden();
});

it('adds and removes the labelled samples for the staging check', function () {
    staff(SystemRole::SuperAdmin);
    $this->artisan('portal:sample-opportunities', ['--closes-in' => 10])->assertSuccessful();

    $tender = Opportunity::query()->where('reference', 'SAMPLE/T/001')->firstOrFail();
    expect(Opportunity::query()->count())->toBe(5)
        ->and($tender->addenda()->count())->toBe(3)
        ->and($tender->wasExtended())->toBeTrue()
        ->and(Opportunity::query()->whereNotNull('archived_at')->pluck('reference')->all())->toBe(['SAMPLE/I/001'])
        ->and(Post::query()->where('type', 'advisory')->count())->toBe(1)
        ->and(Campaign::query()->where('status', 'draft')->count())->toBe(1);
    $this->get('/am/opportunities')->assertOk()->assertSee('ለክልል ጽሕፈት ቤቶች የኔትወርክ መሣሪያዎች አቅርቦት');

    $this->artisan('portal:sample-opportunities', ['--remove' => true])->assertSuccessful();
    expect(Opportunity::query()->withTrashed()->count())->toBe(0)
        ->and(Post::query()->withTrashed()->where('type', 'advisory')->count())->toBe(0)
        ->and(Campaign::query()->count())->toBe(0);
});
