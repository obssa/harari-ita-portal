<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Queries\PublicPosts;
use App\Domain\Content\Support\BodyRenderer;
use App\Domain\Organisation\Models\Leader;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Organisation\Models\PillarTranslation;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\Social\Queries\SocialFeed;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Pillars and forums (PLAN.md §4: each a small site with its leadership,
 * news and contacts; events, galleries, documents and the join form arrive
 * with their modules).
 */
final class PillarController extends Controller
{
    public function index(): View
    {
        $pillars = PublicDirectory::visible(Pillar::query(), app()->getLocale())
            ->with(['logo', 'coverImage'])
            ->orderBy('sort')
            ->get();

        return view('pages.pillars.index', ['pillars' => $pillars]);
    }

    public function show(string $slug, PublicPosts $posts): View
    {
        $locale = app()->getLocale();

        /** @var PillarTranslation|null $translation */
        $translation = PillarTranslation::query()->where('locale', $locale)->where('slug', $slug)->first();
        $pillar = $translation?->pillar()->with(['translations', 'logo', 'coverImage', 'head.translations', 'head.portrait'])->first();
        abort_if($translation === null || $pillar === null || ! $translation->isLive(), 404);

        $people = PublicDirectory::visible($pillar->leaders()->getQuery(), $locale)->with('portrait')->orderBy('rank')->get()
            ->reject(fn (Leader $leader): bool => $leader->id === $pillar->head_leader_id)
            ->values();
        $url = route('pillars.show', ['slug' => $translation->slug]);

        return view('pages.pillars.show', [
            'pillar' => $pillar,
            'translation' => $translation,
            'body' => BodyRenderer::toHtml($translation->body),
            'head' => $pillar->head !== null ? PublicDirectory::shown($pillar->head, $locale) : null,
            'people' => $people,
            'posts' => $posts->linkedTo($locale, 'pillars', $pillar->id),
            'social' => SocialFeed::latest(3, pillarId: $pillar->id),
            'alternates' => PublicDirectory::alternates($pillar, 'pillars.show', 'pillars.index'),
            'image' => PublicDirectory::shareImage($pillar->coverImage, 'landscape', "pillar-{$pillar->id}-{$locale}", $translation->name, __('pillars.title')),
            'jsonLd' => [array_filter([
                '@type' => 'Organization',
                '@id' => $url.'#pillar',
                'name' => $translation->name,
                'description' => $translation->summary,
                'url' => $url,
                'email' => $pillar->email,
                'telephone' => $pillar->phone,
                'sameAs' => array_values($pillar->socialLinks()) ?: null,
                'parentOrganization' => ['@id' => url('/').'#organization'],
            ], fn (mixed $value): bool => $value !== null)],
        ]);
    }
}
