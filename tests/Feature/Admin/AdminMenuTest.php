<?php

use App\Domain\Access\Enums\SystemRole;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Facades\Filament;

/*
 * ADR-0052: the admin menu, grouped by the Agency's work. These tests pin every entry a Super Admin sees, so a
 * regrouping can never drop a page by accident, and the order of the groups in each admin language.
 */

/**
 * @return array<string, list<string>> group label => the paths of its entries, in order
 */
function adminMenu(): array
{
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $menu = [];
    foreach (Filament::getNavigation() as $group) {
        foreach ($group->getItems() as $item) {
            $menu[(string) ($group->getLabel() ?? '')][] = (string) parse_url((string) $item->getUrl(), PHP_URL_PATH);
        }
    }

    return $menu;
}

it('keeps every page in the menu, grouped by the Agency\'s work', function () {
    $this->actingAs(staff(SystemRole::SuperAdmin, mfa: true))->get('/admin')->assertOk();

    expect(adminMenu())->toBe([
        '' => ['/admin', '/admin/notifications'],
        'Content' => ['/admin/homepage', '/admin/homepage-carousel', '/admin/posts', '/admin/pages', '/admin/events', '/admin/check-in',
            '/admin/documents', '/admin/galleries', '/admin/videos', '/admin/media', '/admin/media-folders'],
        'Organisation' => ['/admin/pillars', '/admin/offices', '/admin/leaders'],
        'Services & support' => ['/admin/services', '/admin/tickets', '/admin/sla-policies', '/admin/status-components', '/admin/incidents'],
        'Innovation' => ['/admin/startups', '/admin/programmes', '/admin/applications', '/admin/challenges', '/admin/challenge-teams',
            '/admin/my-judging', '/admin/volunteers'],
        'Projects & data' => ['/admin/projects', '/admin/statistics', '/admin/datasets'],
        'Academy' => ['/admin/courses', '/admin/course-cohorts', '/admin/instructors', '/admin/certificates'],
        'Opportunities' => ['/admin/opportunities'],
        'Communication' => ['/admin/submissions', '/admin/forms', '/admin/campaigns', '/admin/subscribers', '/admin/social-sources',
            '/admin/social-posts', '/admin/media-contacts', '/admin/press-kit', '/admin/announcements'],
        'Insights' => ['/admin/analytics', '/admin/analytics-reports', '/admin/search-terms'],
        'Languages' => ['/admin/translations', '/admin/translation-imports', '/admin/glossary-terms'],
        'Site setup' => ['/admin/settings/site', '/admin/menus', '/admin/categories', '/admin/tags', '/admin/redirects', '/admin/content-sources'],
        'Administration' => ['/admin/users', '/admin/roles', '/admin/settings/security', '/admin/settings/integrations', '/admin/webhooks',
            '/admin/api-keys', '/admin/privacy', '/admin/audit-log', '/admin/health'],
    ]);
});

it('orders the groups the same way in Amharic and Afaan Oromo', function (string $locale) {
    $this->actingAs(staff(SystemRole::SuperAdmin, mfa: true, attributes: ['locale' => $locale]))->get('/admin')->assertOk();
    app()->setLocale($locale);

    $expected = array_map(fn (string $key): string => __($key, [], $locale), AdminPanelProvider::MENU_GROUPS);
    expect(array_values(array_filter(array_keys(adminMenu()))))->toBe($expected);
})->with(['am', 'om']);

it('writes menu labels in sentence case', function () {
    $this->actingAs(staff(SystemRole::SuperAdmin, mfa: true));
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $labels = collect(Filament::getNavigation())->flatMap(fn ($group) => $group->getItems())->map(fn ($item): string => (string) $item->getLabel());
    expect($labels)->toContain('Incidents and maintenance')->toContain('Social media accounts')->toContain('Media library')
        ->not->toContain('Incidents And Maintenance');
});
