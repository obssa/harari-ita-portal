<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Health\Support\LaunchCheck;
use App\Domain\Translation\Support\Locales;
use App\Settings\SiteSettings;

function launchItem(string $key): array
{
    return collect(LaunchCheck::run())->firstWhere('key', $key) ?? [];
}

it('lists what is ready, missing and deferred for the launch', function () {
    staff(SystemRole::SuperAdmin);
    $this->artisan('portal:sample-opportunities')->assertSuccessful();

    expect(launchItem('samples'))->toMatchArray(['status' => LaunchCheck::MISSING])
        ->and(launchItem('samples')['detail'])->toContain('opportunities')
        ->and(launchItem('sms')['status'])->toBe(LaunchCheck::DEFERRED)
        ->and(launchItem('indexing')['status'])->toBe(LaunchCheck::DEFERRED)
        ->and(launchItem('harari')['status'])->toBe(LaunchCheck::DEFERRED)
        ->and(launchItem('directorates')['status'])->toBe(LaunchCheck::MISSING);

    $this->artisan('portal:launch-check')->expectsOutputToContain(' missing, ')->assertSuccessful();
    $this->artisan('portal:launch-check', ['--strict' => true])->assertFailed();

    // One step removes every labelled sample set.
    $this->artisan('portal:remove-samples', ['--force' => true])->expectsOutputToContain('No labelled sample content is left.')->assertSuccessful();
    expect(launchItem('samples')['status'])->toBe(LaunchCheck::READY);
});

it('hides Harari from visitors until it is ready, without breaking its addresses', function () {
    expect(Locales::shown())->not->toContain('har')->and(Locales::isHidden('har'))->toBeTrue();

    $home = $this->get('/en')->assertOk();
    expect($home->getContent())->not->toContain('hreflang="har"')->toContain('hreflang="am"');
    $this->get('/sitemap.xml')->assertOk()->assertDontSee('sitemaps/har.xml', escape: false)->assertSee('sitemaps/am.xml', escape: false);
    $this->get('/har')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    // An administrator shows it again; English and the default language can never be hidden.
    $settings = app(SiteSettings::class);
    $settings->hidden_locales = ['en', 'har'];
    $settings->save();
    expect(Locales::hidden())->toBe(['har']);
    $settings->hidden_locales = [];
    $settings->save();
    expect(Locales::shown())->toContain('har');
    $this->get('/en')->assertSee('hreflang="har"', escape: false);
});
