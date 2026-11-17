<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Enums\AdvisorySeverity;
use App\Domain\Content\Enums\PostType;
use App\Domain\Content\Models\Post;
use App\Domain\Content\Models\PostTranslation;
use App\Domain\Content\Queries\PublicPosts;
use App\Domain\Content\Support\BodyRenderer;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Safety and awareness advisories (PLAN.md §4, ADR-0034): the list by severity, one advisory with the affected
 * systems and the steps to take, and their feed. Incidents go to INSA, the national authority.
 */
final class AdvisoryController extends Controller
{
    public const FEED_LIMIT = 20;

    public function index(Request $request, PublicPosts $posts): View
    {
        $severity = AdvisorySeverity::tryFrom((string) $request->query('severity'));

        return view('pages.advisories.index', [
            'posts' => $posts->paginate(app()->getLocale(), ['type' => PostType::Advisory, 'severity' => $severity]),
            'severity' => $severity,
        ]);
    }

    public function show(string $slug): View
    {
        $locale = app()->getLocale();
        /** @var PostTranslation|null $translation */
        $translation = PostTranslation::query()->where('locale', $locale)->where('slug', $slug)->first();
        $post = $translation?->post()->with(['translations', 'featuredImage', 'categories.translations', 'tags.translations'])->first();
        abort_if($translation === null || $post === null || ! $translation->isLive() || ! $post->isAdvisory(), 404);

        return view('pages.advisories.show', [
            'post' => $post,
            'translation' => $translation,
            'body' => BodyRenderer::toHtml($translation->body),
            'alternates' => NewsController::alternates($post),
            'image' => NewsController::shareImage($post, $translation),
            'recent' => app(PublicPosts::class)->take($locale, 4, ['type' => PostType::Advisory])->reject(fn (Post $other): bool => $other->is($post))->take(3)->values(),
            'jsonLd' => self::jsonLd($post, $translation),
        ]);
    }

    public function rss(): Response
    {
        $locale = app()->getLocale();

        return response()
            ->view('feeds.rss', [
                'items' => $this->items($locale),
                'locale' => $locale,
                'channel' => ['title' => __('site.name').' — '.__('advisories.title'), 'link' => route('advisories.index'), 'description' => __('advisories.description'), 'self' => route('advisories.feed')],
            ])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    /**
     * @return Collection<int, array{url: string, title: string, summary: ?string, html: string, image: null, date: CarbonImmutable, tags: array<int, string>}>
     */
    private function items(string $locale): Collection
    {
        return Post::query()->publishedIn($locale)->where('type', PostType::Advisory->value)
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->get()
            ->map(function (Post $post) use ($locale): ?array {
                $translation = $post->translation($locale, fallback: false);
                if (! $translation instanceof PostTranslation || $translation->published_at === null) {
                    return null;
                }

                /** @var array<int, string> $tags */
                $tags = $post->severity !== null ? [$post->severity->label($locale)] : [];

                return [
                    'url' => $post->url($translation),
                    'title' => ($post->severity !== null ? $post->severity->label($locale).': ' : '').$translation->title,
                    'summary' => $translation->excerpt,
                    'html' => BodyRenderer::toHtml($translation->body),
                    'image' => null,
                    'date' => $translation->published_at,
                    'tags' => $tags,
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $item): int => $item['date']->getTimestamp())
            ->take(self::FEED_LIMIT)
            ->values();
    }

    /**
     * An advisory is an Article with its breadcrumb (PLAN.md §11).
     *
     * @return list<array<string, mixed>>
     */
    public static function jsonLd(Post $post, PostTranslation $translation): array
    {
        $url = $post->url($translation);

        return [
            array_filter([
                '@type' => 'Article',
                '@id' => $url.'#article',
                'headline' => $translation->title,
                'description' => $translation->excerpt,
                'inLanguage' => $translation->locale,
                'datePublished' => $translation->published_at?->toIso8601String(),
                'dateModified' => $translation->updated_at?->toIso8601String(),
                'mainEntityOfPage' => $url,
                'publisher' => ['@id' => url('/').'#organization'],
                'author' => ['@id' => url('/').'#organization'],
            ], fn (mixed $value): bool => $value !== null),
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('nav.home'), 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => __('advisories.title'), 'item' => route('advisories.index')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $translation->title, 'item' => $url],
                ],
            ],
        ];
    }
}
