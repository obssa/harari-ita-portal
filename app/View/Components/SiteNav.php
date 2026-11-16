<?php

namespace App\View\Components;

use App\Domain\Content\Models\Menu;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\PageTranslation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;
use Throwable;

/**
 * The main menu from Admin → Menus (header), in the page's language. Until
 * an editor builds one, the sections of the design (ADR-0062) are shown,
 * each pointing at the site's own listings.
 */
final class SiteNav extends Component
{
    /** @var list<array<string, mixed>> */
    public array $items;

    /**
     * @param  'bar'|'drawer'  $variant  the desktop bar with panels, or the phone menu behind a button
     */
    public function __construct(public string $location = 'header', public string $variant = 'bar')
    {
        $locale = app()->getLocale();
        $items = Menu::resolved($location, $locale);

        if ($items === [] && $location === 'header') {
            $items = self::defaultItems();
        }

        $here = url()->current();
        $this->items = array_map(function (array $item) use ($here): array {
            $item['active'] = self::isActive($item, $here);
            foreach ($item['children'] as $child) {
                $item['active'] = $item['active'] || self::isActive($child, $here);
            }

            return $item;
        }, $items);
    }

    public function render(): View
    {
        return view('components.site-nav');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function isActive(array $item, string $here): bool
    {
        return (is_string($item['current']) && request()->routeIs($item['current'])) || $item['url'] === $here;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function defaultItems(): array
    {
        $link = fn (string $label, string $url, ?string $current, ?string $hint = null): array => [
            'label' => __($label), 'url' => $url, 'external' => false, 'current' => $current, 'hint' => $hint === null ? null : __($hint), 'children' => [],
        ];
        $section = fn (string $label, string $desc, string $url, ?string $current, array $children): array => [
            ...$link($label, $url, $current), 'description' => __($desc), 'children' => $children,
        ];

        $about = self::aboutPages(app()->getLocale());

        // The Agency's site map (PLAN.md §3). The sections of modules still to come (services, innovation,
        // academy, opportunities) join the menu when their routes exist, so nothing links to a missing page.
        $items = [
            $section('nav.about', 'nav.about_desc', $about[0]['url'] ?? route('structure'), null, [
                ...$about,
                $link('leaders.title', route('leaders.index'), 'leaders.*', 'nav.leaders_hint'),
                $link('structure.title', route('structure'), 'structure', 'nav.structure_hint'),
                $link('offices.title', route('offices.index'), 'offices.*', 'nav.offices_hint'),
                $link('nav.pillars', route('pillars.index'), 'pillars.*', 'pillars.description'),
            ]),
        ];
        if (Route::has('services.index')) {
            $items[] = $section('nav.services', 'nav.services_desc', route('services.index'), null, array_values(array_filter([
                $link('nav.services_catalogue', route('services.index'), 'services.*', 'nav.services_catalogue_hint'),
                Route::has('support.create') ? $link('nav.support_new', route('support.create'), 'support.create', 'nav.support_new_hint') : null,
                Route::has('support.lookup') ? $link('nav.support_track', route('support.lookup'), 'support.lookup', 'nav.support_track_hint') : null,
                Route::has('status') ? $link('nav.status', route('status'), 'status', 'nav.status_hint') : null,
            ])));
        }
        foreach (['innovation', 'academy'] as $module) {
            if (Route::has("{$module}.index")) {
                $items[] = $link("nav.{$module}", route("{$module}.index"), "{$module}.*");
            }
        }
        $items[] = $section('nav.projects_data', 'nav.projects_data_desc', route('projects.index'), null, [
            $link('projects.title', route('projects.index'), 'projects.index', 'nav.projects_hint'),
            $link('nav.achievements', route('projects.achievements'), 'projects.achievements', 'nav.achievements_hint'),
        ]);
        $items[] = $section('nav.media', 'nav.media_desc', route('news.index'), null, [
            $link('nav.news', route('news.index'), 'news.*', 'nav.news_hint'),
            $link('nav.press_releases', route('news.index', ['type' => 'press_release']), null, 'nav.press_releases_hint'),
            $link('nav.statements', route('news.index', ['type' => 'statement']), null, 'nav.statements_hint'),
            $link('events.title', route('events.index'), 'events.*', 'nav.events_hint'),
            $link('gallery.title', route('gallery.index'), 'gallery.*', 'nav.gallery_hint'),
            $link('nav.videos', route('videos.index'), 'videos.*', 'nav.videos_hint'),
            $link('social.hub.title', route('social'), 'social', 'nav.social_hint'),
            $link('press.title', route('press'), 'press*', 'nav.press_hint'),
        ]);
        $items[] = $section('nav.resources', 'nav.resources_desc', route('documents.index'), null, [
            $link('documents.title', route('documents.index'), 'documents.index', 'nav.documents_hint'),
            $link('nav.doc_programme', route('documents.index', ['type' => 'programme']), null, 'nav.doc_programme_hint'),
            $link('nav.doc_bylaws', route('documents.index', ['type' => 'bylaw']), null, 'nav.doc_bylaws_hint'),
            $link('nav.doc_policies', route('documents.index', ['type' => 'policy']), null, 'nav.doc_policies_hint'),
            $link('nav.doc_reports', route('documents.index', ['type' => 'report']), null, 'nav.doc_reports_hint'),
            $link('nav.notices', route('documents.notices'), 'documents.notices', 'nav.notices_hint'),
        ]);
        $items[] = $section('nav.get_involved', 'nav.get_involved_desc', route('get-involved'), 'get-involved', [
            $link('nav.volunteer', route('volunteer.create'), 'volunteer.*', 'nav.volunteer_hint'),
            $link('events.title', route('events.index'), 'events.*', 'nav.events_hint'),
            $link('nav.newsletter', route('newsletter.create'), 'newsletter.*', 'nav.newsletter_hint'),
            $link('nav.contact', route('contact'), 'contact'),
        ]);

        return $items;
    }

    /**
     * The published "about" page and its published pages in a language
     * (ADR-0063), first in the About section; none until they exist.
     *
     * @return list<array<string, mixed>>
     */
    private static function aboutPages(string $locale): array
    {
        try {
            return Cache::remember("menu:about:{$locale}", 600, function () use ($locale): array {
                $translation = PageTranslation::query()->with('page')->where('locale', $locale)->where('slug', 'about')->first();
                $page = $translation?->page;
                if (! $page instanceof Page || $page->parent_id !== null || ! $translation->isLive()) {
                    return [];
                }

                $item = fn (PageTranslation $t, string $path): array => [
                    'label' => $t->title, 'url' => route('page.show', ['locale' => $locale, 'path' => $path]),
                    'external' => false, 'current' => null, 'hint' => $t->excerpt, 'children' => [],
                ];
                $items = [$item($translation, 'about')];
                foreach ($page->children()->with('translations')->orderBy('sort')->get() as $child) {
                    $childTranslation = $child->translation($locale, fallback: false);
                    if ($childTranslation instanceof PageTranslation && $childTranslation->isLive() && filled($childTranslation->slug)) {
                        $items[] = $item($childTranslation, "about/{$childTranslation->slug}");
                    }
                }

                return $items;
            });
        } catch (Throwable) {
            return [];
        }
    }
}
