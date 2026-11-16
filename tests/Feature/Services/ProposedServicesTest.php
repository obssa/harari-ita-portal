<?php

use App\Console\Commands\ProposedServicesCommand;
use App\Domain\Access\Enums\SystemRole;
use App\Domain\Content\Models\Category;
use App\Domain\Services\Actions\SaveServiceItem;
use App\Domain\Services\Models\ServiceItem;

it('loads every proposed service in three languages, marked proposed, as drafts unless asked to publish', function () {
    staff(SystemRole::SuperAdmin);
    $data = json_decode((string) file_get_contents(base_path(ProposedServicesCommand::DATA)), true);

    $this->artisan('portal:proposed-services')->assertSuccessful();

    expect(ServiceItem::query()->count())->toBe(count($data['services']))
        ->and(ServiceItem::query()->where('proposed', false)->count())->toBe(0)
        ->and(Category::query()->where('type', Category::TYPE_SERVICE)->count())->toBe(count($data['categories']));
    foreach (ServiceItem::query()->with('translations')->get() as $service) {
        expect($service->translations->pluck('locale')->sort()->values()->all())->toBe(['am', 'en', 'om'])
            ->and($service->translation('en')->stepList())->not->toBeEmpty()
            ->and($service->category_id)->not->toBeNull();
    }
    $this->get('/en/services')->assertSee(__('services.empty', [], 'en'));

    // Running it again adds nothing; --publish here publishes nothing new because all exist.
    $this->artisan('portal:proposed-services')->assertSuccessful();
    expect(ServiceItem::query()->count())->toBe(count($data['services']));
});

it('shows a published proposed service with its notice, and removes only those still unconfirmed', function () {
    staff(SystemRole::SuperAdmin);
    $this->artisan('portal:proposed-services', ['--publish' => true])->assertSuccessful();

    $service = ServiceItem::query()->get()->first(fn (ServiceItem $s): bool => $s->title('en') === 'Digital literacy training');
    $this->get('/en/services')->assertSee('Digital literacy training')->assertSee(__('services.proposed', [], 'en'));
    $this->get('/en/services/'.$service->translation('en')->slug)->assertOk()->assertSee(__('services.proposed_notice', [], 'en'));
    $this->get('/am/services/'.$service->translation('am')->slug)->assertOk()->assertSee('የዲጂታል ዕውቀት ሥልጠና');

    // The Agency confirms one: it stays when the rest are removed, and loses its notice.
    app(SaveServiceItem::class)->handle($service, ['proposed' => false, 'channels' => ['in_person'], 'category_id' => $service->category_id,
        'translations' => ['en' => ['title' => 'Digital literacy training', 'published' => true]]], staff(SystemRole::ServiceOwner));
    $this->get('/en/services/'.$service->translation('en')->slug)->assertDontSee(__('services.proposed_notice', [], 'en'));

    $this->artisan('portal:proposed-services', ['--remove' => true])->assertSuccessful();
    expect(ServiceItem::query()->withTrashed()->count())->toBe(1)
        ->and(Category::query()->where('type', Category::TYPE_SERVICE)->count())->toBe(1);
});
