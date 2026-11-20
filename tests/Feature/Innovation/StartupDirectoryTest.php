<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Innovation\Actions\ReviewStartup;
use App\Domain\Innovation\Actions\SaveStartup;
use App\Domain\Innovation\Enums\LabelStatus;
use App\Domain\Innovation\Enums\ListingStatus;
use App\Domain\Innovation\Mail\StartupReceivedMail;
use App\Domain\Innovation\Mail\StartupReviewedMail;
use App\Domain\Innovation\Models\Startup;
use App\Filament\Resources\Startups\Pages\EditStartup;
use App\Support\SpamGuard;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function listingForm(array $overrides = []): array
{
    return [
        'name' => 'Sample Harar Coders',
        'summary' => 'Sample: a startup that builds booking software for guesthouses in Harar.',
        'products' => "Sample booking app\nSample payment link",
        'stage' => 'mvp',
        'founded_year' => '2024',
        'team_size' => '4',
        'web_address' => 'https://example.org',
        'label_claimed' => '1',
        'founder_name' => 'Sample Founder',
        'founder_email' => 'founder@example.org',
        'founder_phone' => '0911556677',
        'consent' => '1',
        SpamGuard::STARTED => Crypt::encryptString((string) (time() - 10)),
        ...$overrides,
    ];
}

it('takes a listing request that stays off the site until the Agency lists it', function () {
    Mail::fake();
    $this->get('/en/innovation/startups/apply')->assertOk()->assertSee(__('innovation.public.apply_title', [], 'en'));

    $response = $this->post('/en/innovation/startups/apply', listingForm());
    $startup = Startup::query()->firstOrFail();
    $response->assertRedirect('/en/innovation/startups/listing/'.$startup->reference);

    expect($startup->listing_status)->toBe(ListingStatus::Submitted)
        ->and($startup->label_status)->toBe(LabelStatus::SelfDeclared)
        ->and($startup->reference)->toMatch('/^'.Startup::REFERENCE_PATTERN.'$/');
    $raw = DB::table('startups')->where('id', $startup->id)->first();
    expect($raw->founder_email)->not->toContain('founder@example.org');
    Mail::assertQueued(StartupReceivedMail::class);

    $this->get('/en/innovation/startups/listing/'.$startup->reference)->assertOk()->assertSee(__('innovation.listing.submitted', [], 'en'));
    $this->get('/en/innovation/startups')->assertOk()->assertDontSee('Sample Harar Coders')->assertSee(__('innovation.startups.empty_title', [], 'en'));
    $slug = $startup->translation('en')->slug;
    $this->get('/en/innovation/startups/'.$slug)->assertNotFound();
});

it('refuses a listing request without consent, an email or a real pitch', function () {
    $this->post('/en/innovation/startups/apply', listingForm(['consent' => null]))->assertSessionHasErrors('consent');
    $this->post('/en/innovation/startups/apply', listingForm(['founder_email' => '']))->assertSessionHasErrors('founder_email');
    $this->post('/en/innovation/startups/apply', listingForm(['summary' => 'short']))->assertSessionHasErrors('summary');
    expect(Startup::query()->count())->toBe(0);
});

it('lists a startup after review, verifies its label, and filters the directory', function () {
    Mail::fake();
    $this->post('/en/innovation/startups/apply', listingForm());
    $startup = Startup::query()->firstOrFail();
    $officer = staff(SystemRole::InnovationOfficer);

    expect(fn () => app(ReviewStartup::class)->decide($startup, ListingStatus::Declined, '', $officer))->toThrow(ValidationException::class);

    app(ReviewStartup::class)->decide($startup, ListingStatus::Listed, null, $officer);
    Mail::assertQueued(StartupReviewedMail::class);
    $startup->refresh();
    expect($startup->listing_status)->toBe(ListingStatus::Listed)->and($startup->listed_at)->not->toBeNull();

    $this->get('/en/innovation/startups')->assertSee('Sample Harar Coders')->assertSee(__('innovation.label.self_declared', [], 'en'));
    $this->get('/en/innovation/startups?label=verified')->assertDontSee('Sample Harar Coders');

    app(ReviewStartup::class)->verifyLabel($startup, true, $officer);
    $this->get('/en/innovation/startups?label=verified')->assertSee('Sample Harar Coders');
    $this->get('/en/innovation/startups?stage=growth')->assertDontSee('Sample Harar Coders');

    $slug = $startup->translation('en')->slug;
    $this->get('/en/innovation/startups/'.$slug)->assertOk()
        ->assertSee('Sample booking app')
        ->assertSee(__('innovation.label.verified', [], 'en'))
        ->assertDontSee('founder@example.org')
        ->assertSee('"@type":"Organization"', escape: false);
    $this->get('/en/search?q=guesthouses')->assertSee('Sample Harar Coders');

    // Archiving takes it off the site again.
    app(ReviewStartup::class)->decide($startup, ListingStatus::Archived, null, $officer);
    $this->get('/en/innovation/startups/'.$slug)->assertNotFound();
});

it('lets founders follow the review only with the reference and their contact', function () {
    Mail::fake();
    $this->post('/en/innovation/startups/apply', listingForm());
    $startup = Startup::query()->firstOrFail();
    $this->flushSession();

    $this->get('/en/innovation/startups/listing/'.$startup->reference)->assertOk()->assertDontSee('Sample Harar Coders');
    $this->post('/en/innovation/startups/listing', ['reference' => $startup->reference, 'contact' => 'other@example.org'])->assertSessionHasErrors('contact');
    $this->post('/en/innovation/startups/listing', ['reference' => $startup->reference, 'contact' => '0911 55 66 77'])
        ->assertRedirect('/en/innovation/startups/listing/'.$startup->reference);
    $this->get('/en/innovation/startups/listing/'.$startup->reference)->assertSee('Sample Harar Coders');
});

it('lets innovation officers review in the admin and keeps the founders\' contact there', function () {
    Mail::fake();
    $this->post('/en/innovation/startups/apply', listingForm());
    $startup = Startup::query()->firstOrFail();

    $this->actingAs(staff(SystemRole::Author))->get('/admin/startups')->assertForbidden();
    $this->actingAs(staff(SystemRole::InnovationOfficer));
    $this->get('/admin/startups')->assertOk()->assertSee('Sample Harar Coders');

    livewire(EditStartup::class, ['record' => $startup->getRouteKey()])
        ->assertFormSet(['founder_email' => 'founder@example.org', 'stage' => 'mvp'])
        ->callAction('review', data: ['status' => 'changes_requested', 'note' => 'Sample: add your products.'])
        ->assertHasNoActionErrors();
    expect($startup->refresh()->listing_status)->toBe(ListingStatus::ChangesRequested);

    // A staff edit never sets "verified" or publishes by itself.
    app(SaveStartup::class)->handle($startup, ['stage' => 'growth', 'label_status' => 'verified', 'translations' => ['en' => ['name' => 'Sample Harar Coders', 'published' => true]]], staff(SystemRole::InnovationOfficer));
    expect($startup->refresh()->label_status)->toBe(LabelStatus::SelfDeclared)
        ->and($startup->translation('en')->isLive())->toBeFalse();
});

it('moves a listing only along the allowed decisions, on the locked startup, and dates its last listing (ADR-0054)', function () {
    Mail::fake();
    $this->post('/en/innovation/startups/apply', listingForm());
    $startup = Startup::query()->firstOrFail();
    $officer = staff(SystemRole::InnovationOfficer);
    expect(ListingStatus::Declined->nextStatuses())->toBe([])
        ->and(ListingStatus::Archived->canMoveTo(ListingStatus::Listed))->toBeTrue()
        ->and(ListingStatus::Listed->canMoveTo(ListingStatus::Declined))->toBeFalse();

    $this->travelTo(now()->subDays(10));
    app(ReviewStartup::class)->decide($startup, ListingStatus::Listed, null, $officer);
    $this->travelBack();
    $first = $startup->listed_at;
    expect($first)->not->toBeNull();

    // A screen loaded before the archive cannot decline the startup; archiving clears the listing date.
    $stale = Startup::query()->findOrFail($startup->id);
    app(ReviewStartup::class)->decide($startup, ListingStatus::Archived, null, $officer);
    expect($startup->listed_at)->toBeNull()
        ->and(fn () => app(ReviewStartup::class)->decide($stale, ListingStatus::Declined, 'Sample note', $officer))->toThrow(ValidationException::class);

    // Listed again, it is dated from today, so the directory shows it with the newest.
    app(ReviewStartup::class)->decide($startup, ListingStatus::Listed, null, $officer);
    expect($startup->listing_status)->toBe(ListingStatus::Listed)
        ->and($startup->listed_at->greaterThan($first))->toBeTrue();

    // Declined is final, and the admin offers only the allowed decisions.
    app(ReviewStartup::class)->decide($startup, ListingStatus::ChangesRequested, 'Sample: please add your team size.', $officer);
    app(ReviewStartup::class)->decide($startup, ListingStatus::Declined, 'Sample: not a startup.', $officer);
    expect(fn () => app(ReviewStartup::class)->decide($startup, ListingStatus::Listed, null, $officer))->toThrow(ValidationException::class);
    $this->actingAs($officer);
    livewire(EditStartup::class, ['record' => $startup->getRouteKey()])
        ->callAction('review', data: ['status' => 'listed', 'note' => null])
        ->assertHasActionErrors(['status']);
    expect($startup->refresh()->listing_status)->toBe(ListingStatus::Declined);
});
