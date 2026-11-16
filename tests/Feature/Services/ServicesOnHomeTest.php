<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Homepage\Enums\SectionKey;
use App\Domain\Homepage\Models\HomepageSection;
use App\Domain\Homepage\Support\HomepageLayout;
use App\Domain\Services\Actions\SaveServiceItem;
use App\Domain\Services\Models\ServiceItem;
use App\Domain\Support\Actions\OpenTicket;
use App\Domain\Support\Actions\UpdateTicket;
use Illuminate\Support\Facades\Mail;

it('shows the top services on the homepage, featured first, with the ways into the service desk', function () {
    $owner = staff(SystemRole::ServiceOwner);
    app(SaveServiceItem::class)->handle(new ServiceItem, ['channels' => ['in_person'], 'rank' => 1, 'translations' => ['en' => ['title' => 'Sample desk service', 'published' => true]]], $owner);
    app(SaveServiceItem::class)->handle(new ServiceItem, ['channels' => ['online'], 'external_url' => 'https://example.org', 'featured' => true, 'rank' => 5, 'translations' => ['en' => ['title' => 'Sample featured service', 'published' => true]]], $owner);

    $this->get('/en')
        ->assertOk()
        ->assertSee(__('homepage.section.services', [], 'en'))
        ->assertSeeInOrder(['Sample featured service', 'Sample desk service'])
        ->assertSee(__('homepage.hero.services', [], 'en'))
        ->assertSee('/en/support/track', escape: false)
        ->assertSee('/en/status', escape: false);
});

it('hides the services section until a service is published', function () {
    $this->get('/en')->assertOk()->assertDontSee(__('homepage.section.services', [], 'en'));
});

it('adds live figures to the figures strip once they are above zero', function () {
    Mail::fake();
    HomepageLayout::all();
    HomepageSection::query()->where('key', SectionKey::Counters->value)->update(['enabled' => true]);

    $this->get('/en')->assertDontSee(__('homepage.live.services_online', [], 'en'));

    app(SaveServiceItem::class)->handle(new ServiceItem, ['channels' => ['online'], 'external_url' => 'https://example.org', 'translations' => ['en' => ['title' => 'Sample online', 'published' => true]]], staff(SystemRole::ServiceOwner));
    $ticket = app(OpenTicket::class)->handle(['requester_type' => 'resident', 'name' => 'Sample Person', 'email' => 'p@example.org', 'category' => 'other', 'subject' => 'Sample subject', 'description' => 'Sample description text.', 'consent' => true], [], 'en');
    app(UpdateTicket::class)->handle($ticket, ['status' => 'resolved'], staff(SystemRole::ServiceDeskAgent));

    $this->get('/en')
        ->assertSee(__('homepage.live.services_online', [], 'en'))
        ->assertSee(__('homepage.live.tickets_resolved', [], 'en'));
});
