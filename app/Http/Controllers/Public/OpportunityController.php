<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Support\BodyRenderer;
use App\Domain\Opportunities\Enums\OpportunityType;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Opportunities\Models\OpportunityAddendum;
use App\Domain\Opportunities\Models\OpportunityTranslation;
use App\Domain\Opportunities\Support\DeadlineCalendar;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Opportunities on the public site (ADR-0032): the open and upcoming ones by nearest deadline with the archive
 * below, one opportunity with its documents and log, its deadline as a calendar file, and a feed of opportunities
 * and their log entries.
 */
final class OpportunityController extends Controller
{
    public const ARCHIVE_PER_PAGE = 12;

    public const FEED_LIMIT = 30;

    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $type = OpportunityType::tryFrom((string) $request->query('type'));
        $base = fn () => PublicDirectory::visible(Opportunity::query(), $locale)
            ->when($type !== null, fn ($q) => $q->where('type', $type?->value))
            ->with(['office.translations']);

        $counts = PublicDirectory::visible(Opportunity::query(), $locale)->current()
            ->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type')->map(fn (mixed $n): int => (int) $n)->all();

        return view('pages.opportunities.index', [
            'current' => $base()->current()->orderByRaw('opens_at is not null and opens_at > ?', [CarbonImmutable::now()])
                ->orderBy('closes_at')->limit(60)->get(),
            'past' => $base()->past()->orderByDesc('closes_at')->paginate(self::ARCHIVE_PER_PAGE, pageName: 'archive')->withQueryString()->fragment('opportunities-archive'),
            'type' => $type,
            'counts' => $counts,
        ]);
    }

    /** The fork's notices page (ADR-0026 of the fork) became this one. */
    public function notices(): RedirectResponse
    {
        return redirect()->route('opportunities.index', status: 301);
    }

    public function show(string $slug): View
    {
        [$opportunity, $translation] = $this->find($slug);
        $locale = app()->getLocale();
        $opportunity->load([
            'office.translations', 'pillar.translations',
            'documents' => fn ($q) => $q->publicAccess()->with('translations.file'),
            'addenda.document.translations',
        ]);
        $documents = $opportunity->documents
            ->map(fn ($document): ?array => ($shown = PublicDirectory::shown($document, $locale)) !== null ? ['document' => $document, 'translation' => $shown] : null)
            ->filter()->values();

        return view('pages.opportunities.show', [
            'opportunity' => $opportunity,
            'translation' => $translation,
            'body' => BodyRenderer::toHtml($translation->body),
            'documents' => $documents,
            'addenda' => $opportunity->addenda,
            'office' => $opportunity->office !== null ? PublicDirectory::shown($opportunity->office, $locale) : null,
            'pillar' => $opportunity->pillar !== null ? PublicDirectory::shown($opportunity->pillar, $locale) : null,
            'alternates' => PublicDirectory::alternates($opportunity, 'opportunities.show', 'opportunities.index'),
            'jsonLd' => self::jsonLd($opportunity, $translation),
        ]);
    }

    public function ics(string $slug): Response
    {
        [$opportunity, $translation] = $this->find($slug);
        abort_if($opportunity->isCancelled(), 404);

        return response(DeadlineCalendar::render($opportunity, $translation), 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="deadline.ics"',
        ]);
    }

    /** RSS 2.0: the opportunities of this language and their log entries, newest first. */
    public function rss(): Response
    {
        $locale = app()->getLocale();

        return response()
            ->view('feeds.rss', [
                'items' => self::feedItems($locale),
                'locale' => $locale,
                'channel' => ['title' => __('site.name').' — '.__('opportunities.title'), 'link' => route('opportunities.index'), 'description' => __('opportunities.description'), 'self' => route('opportunities.feed')],
            ])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    /**
     * @return Collection<int, array{url: string, title: string, summary: ?string, html: string, image: null, date: CarbonImmutable, tags: list<string>}>
     */
    public static function feedItems(string $locale): Collection
    {
        $items = collect();
        $opportunities = PublicDirectory::visible(Opportunity::query(), $locale)->with(['addenda'])
            ->orderByDesc('created_at')->limit(self::FEED_LIMIT)->get();
        foreach ($opportunities as $opportunity) {
            $shown = PublicDirectory::shown($opportunity, $locale);
            if (! $shown instanceof OpportunityTranslation) {
                continue;
            }
            $url = route('opportunities.show', ['locale' => $shown->locale, 'slug' => $shown->slug]);
            $items->push([
                'url' => $url,
                'title' => $shown->title,
                'summary' => $shown->summary,
                'html' => BodyRenderer::toHtml($shown->body),
                'image' => null,
                'date' => $shown->published_at ?? $opportunity->created_at ?? CarbonImmutable::now(),
                'tags' => [$opportunity->type->label($locale)],
            ]);
            foreach ($opportunity->addenda as $addendum) {
                /** @var OpportunityAddendum $addendum */
                $items->push([
                    'url' => $url.'#addendum-'.$addendum->number,
                    'title' => $shown->title.' · '.$addendum->kind->label($locale).' '.$addendum->number.': '.$addendum->title($locale),
                    'summary' => $addendum->body($locale) !== '' ? $addendum->body($locale) : null,
                    'html' => e($addendum->body($locale)),
                    'image' => null,
                    'date' => $addendum->published_at,
                    'tags' => [$opportunity->type->label($locale), $addendum->kind->label($locale)],
                ]);
            }
        }

        return $items->sortByDesc(fn (array $item): int => $item['date']->getTimestamp())->take(self::FEED_LIMIT)->values();
    }

    /**
     * JobPosting for vacancies and internships (PLAN.md §11), and the breadcrumb.
     *
     * @return list<array<string, mixed>>
     */
    public static function jsonLd(Opportunity $opportunity, OpportunityTranslation $translation): array
    {
        $url = route('opportunities.show', ['slug' => $translation->slug]);
        $items = [];
        if ($opportunity->type->isJob() && ! $opportunity->isCancelled()) {
            $description = trim(e((string) $translation->summary).' '.BodyRenderer::toHtml($translation->body));
            $items[] = array_filter([
                '@type' => 'JobPosting',
                '@id' => $url.'#job',
                'title' => $translation->title,
                'description' => $description !== '' ? $description : e($translation->title),
                'datePosted' => ($translation->published_at ?? $opportunity->created_at)?->toIso8601String(),
                'validThrough' => $opportunity->closes_at->toIso8601String(),
                'employmentType' => $opportunity->type === OpportunityType::Internship ? 'INTERN' : $opportunity->employment_type?->schemaOrg(),
                'totalJobOpenings' => $opportunity->positions,
                'hiringOrganization' => ['@id' => url('/').'#organization'],
                'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressRegion' => 'Harari', 'addressCountry' => 'ET']],
                'identifier' => filled($opportunity->reference) ? ['@type' => 'PropertyValue', 'name' => __('site.name'), 'value' => $opportunity->reference] : null,
                'directApply' => false,
                'inLanguage' => $translation->locale,
                'url' => $url,
            ], fn (mixed $value): bool => $value !== null);
        }
        $items[] = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('nav.home'), 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('opportunities.title'), 'item' => route('opportunities.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $translation->title, 'item' => $url],
            ],
        ];

        return $items;
    }

    /**
     * @return array{0: Opportunity, 1: OpportunityTranslation}
     */
    private function find(string $slug): array
    {
        /** @var OpportunityTranslation|null $translation */
        $translation = OpportunityTranslation::query()->where('locale', app()->getLocale())->where('slug', $slug)->first();
        $opportunity = $translation?->opportunity()->with('translations')->first();
        abort_if($translation === null || $opportunity === null || ! $translation->isLive(), 404);

        return [$opportunity, $translation];
    }
}
