<?php

use App\Domain\Access\Enums\ScopeType;
use App\Domain\Access\Enums\SystemRole;
use App\Domain\Content\Models\Category;
use App\Domain\Organisation\Actions\SaveOffice;
use App\Domain\Organisation\Models\Office;
use App\Domain\Services\Actions\SaveServiceItem;
use App\Domain\Services\Models\ServiceItem;
use App\Filament\Resources\Services\Pages\CreateServiceItem;
use App\Filament\Resources\Services\Pages\EditServiceItem;
use App\Models\User;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

function serviceOffice(string $name = 'Sample directorate'): Office
{
    return app(SaveOffice::class)->handle(new Office, ['type' => 'directorate', 'translations' => ['en' => ['name' => $name, 'published' => true]]], staff(SystemRole::CommunicationsEditor));
}

function serviceCategory(string $name = 'Sample category'): Category
{
    $category = Category::query()->create(['type' => Category::TYPE_SERVICE]);
    $category->putTranslation('en', ['name' => $name]);

    return $category;
}

/**
 * @param  array<string, array<string, mixed>>  $translations
 * @param  array<string, mixed>  $data
 */
function sampleService(array $translations, array $data = [], ?User $actor = null): ServiceItem
{
    return app(SaveServiceItem::class)->handle(new ServiceItem, ['channels' => ['in_person'], 'translations' => $translations, ...$data], $actor ?? staff(SystemRole::ServiceOwner));
}

it('publishes a service in four languages with its steps, documents and questions', function () {
    $office = serviceOffice();
    $category = serviceCategory();
    $service = sampleService([
        'en' => [
            'title' => 'Sample email account for a public office',
            'summary' => 'Sample summary.',
            'eligibility' => 'Sample public offices in the region.',
            'documents' => "- Sample request letter\n- 10 sample photos",
            'steps' => "1. Sample first step\n2. Sample second step\n3. Sample third step",
            'fees' => 'Free',
            'duration' => '3 working days',
            'faq' => [['question' => 'Sample question?', 'answer' => 'Sample answer.'], ['question' => '', 'answer' => 'dropped']],
            'published' => true,
        ],
        'am' => ['title' => 'ናሙና የኢሜይል አካውንት', 'published' => true],
        'om' => ['title' => 'Herrega imeelii fakkeenyaa', 'published' => true],
        'har' => ['title' => 'Sample Harari title', 'published' => true],
    ], ['office_id' => $office->id, 'category_id' => $category->id, 'channels' => ['online', 'in_person'], 'external_url' => 'https://example.org/apply', 'audiences' => ['government']]);

    expect($service->translations()->count())->toBe(4)
        ->and($service->isOnline())->toBeTrue()
        ->and($service->translation('en')->documentList())->toBe(['Sample request letter', '10 sample photos'])
        ->and($service->translation('en')->stepList())->toHaveCount(3)
        ->and($service->translation('en')->faqList())->toBe([['question' => 'Sample question?', 'answer' => 'Sample answer.']]);

    $slug = $service->translation('en')->slug;
    $this->get('/en/services')->assertOk()->assertSee('Sample email account for a public office')->assertSee(__('services.channel.online', [], 'en'));
    $this->get("/en/services/{$slug}")
        ->assertOk()
        ->assertSeeInOrder(['Sample public offices in the region.', 'Sample request letter', '10 sample photos', 'Sample first step', 'Sample third step', 'Sample question?'])
        ->assertSee('https://example.org/apply', escape: false)
        ->assertSee('"@type":"GovernmentService"', escape: false)
        ->assertSee('Sample directorate');

    $amSlug = $service->translation('am')->slug;
    $this->get("/am/services/{$amSlug}")->assertOk()->assertSee('ናሙና የኢሜይል አካውንት');
});

it('hides drafts and filters the catalogue by channel, audience, category and search', function () {
    $category = serviceCategory();
    sampleService(['en' => ['title' => 'Sample online service', 'published' => true]], ['channels' => ['online'], 'external_url' => 'https://example.org', 'category_id' => $category->id]);
    sampleService(['en' => ['title' => 'Sample desk service', 'published' => true]], ['audiences' => ['residents']]);
    sampleService(['en' => ['title' => 'Sample draft service']]);

    $this->get('/en/services')->assertOk()->assertSee('Sample online service')->assertSee('Sample desk service')->assertDontSee('Sample draft service');
    $this->get('/en/services?channel=online')->assertSee('Sample online service')->assertDontSee('Sample desk service');
    $this->get('/en/services?audience=residents')->assertSee('Sample desk service')->assertDontSee('Sample online service');
    $this->get('/en/services?category='.$category->id)->assertSee('Sample online service')->assertDontSee('Sample desk service');
    $this->get('/en/services?q=desk')->assertSee('Sample desk service')->assertDontSee('Sample online service');
    $this->get('/en/services?q=nothing-like-this')->assertSee(__('services.none_match', [], 'en'));

    $draft = ServiceItem::query()->get()->first(fn (ServiceItem $s): bool => $s->title() === 'Sample draft service');
    $this->get('/en/services/'.$draft->translation('en')->slug)->assertNotFound();
});

it('refuses an online service without an https link and a service without a channel', function () {
    expect(fn () => sampleService(['en' => ['title' => 'Sample']], ['channels' => ['online']]))->toThrow(ValidationException::class)
        ->and(fn () => sampleService(['en' => ['title' => 'Sample']], ['channels' => ['online'], 'external_url' => 'http://example.org']))->toThrow(ValidationException::class)
        ->and(fn () => sampleService(['en' => ['title' => 'Sample']], ['channels' => []]))->toThrow(ValidationException::class);
});

it('lets a service owner scoped to a directorate change only that directorate\'s services', function () {
    $own = serviceOffice('Sample own directorate');
    $other = serviceOffice('Sample other directorate');
    $owner = User::factory()->create();
    $owner->assignScopedRole(SystemRole::ServiceOwner, ScopeType::Office, $own->id);
    $owner->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP'])->saveQuietly();

    $mine = sampleService(['en' => ['title' => 'Sample own service']], ['office_id' => $own->id], $owner);
    $theirs = sampleService(['en' => ['title' => 'Sample other service']], ['office_id' => $other->id]);

    expect($owner->can('update', $mine))->toBeTrue()
        ->and($owner->can('update', $theirs))->toBeFalse()
        ->and(fn () => sampleService(['en' => ['title' => 'Sample']], ['office_id' => $other->id], $owner))->toThrow(ValidationException::class);
});

it('creates and edits a service from the admin, and keeps it from staff without the permission', function () {
    $this->actingAs(staff(SystemRole::Author))->get('/admin/services')->assertForbidden();
    $this->actingAs(staff(SystemRole::ServiceDeskAgent))->get('/admin/services')->assertOk();

    $this->actingAs(staff(SystemRole::ServiceOwner));
    livewire(CreateServiceItem::class)
        ->fillForm(['channels' => ['phone'], 'translations' => ['en' => ['title' => 'Sample phone help', 'steps' => "Call\nExplain", 'published' => true]]])
        ->call('create')
        ->assertHasNoFormErrors();

    $service = ServiceItem::query()->firstOrFail();
    livewire(EditServiceItem::class, ['record' => $service->getRouteKey()])
        ->assertFormSet(['translations.en.title' => 'Sample phone help', 'translations.en.steps' => "Call\nExplain", 'translations.en.published' => true])
        ->fillForm(['translations.en.fees' => 'Free'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($service->refresh()->translation('en')->fees)->toBe('Free');
    $this->get('/en/search?q=phone')->assertOk()->assertSee('Sample phone help');
});
