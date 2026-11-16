<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\Category;
use App\Domain\Content\Queries\PublicPosts;
use App\Domain\Content\Support\BodyRenderer;
use App\Domain\Innovation\Enums\LabelStatus;
use App\Domain\Innovation\Enums\StartupStage;
use App\Domain\Innovation\Models\Startup;
use App\Domain\Innovation\Models\StartupTranslation;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The startups directory (PLAN.md §4; ADR-0020): listed startups only, filtered by sector, stage and label, and a
 * profile per startup. The label is shown as self-declared or verified by the Agency; the law itself is linked to
 * the Ministry, not restated.
 */
final class StartupController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $search = mb_substr(trim((string) $request->query('q')), 0, 80);
        $stage = StartupStage::tryFrom((string) $request->query('stage'));
        $label = LabelStatus::tryFrom((string) $request->query('label'));
        $sectors = Category::query()->where('type', Category::TYPE_SECTOR)->with('translations')->orderBy('sort')->get()
            ->filter(fn (Category $c): bool => $c->name($locale) !== '')->values();
        $sector = $sectors->firstWhere('id', (string) $request->query('sector'));

        $startups = PublicDirectory::visible(Startup::query()->listed(), $locale)
            ->when($stage, fn (Builder $q, StartupStage $s) => $q->where('stage', $s->value))
            ->when($label, fn (Builder $q, LabelStatus $l) => $q->where('label_status', $l->value))
            ->when($sector, fn (Builder $q, Category $c) => $q->where('sector_id', $c->id))
            ->when($search !== '', fn (Builder $q) => $q->whereHas('translations', fn (Builder $t) => $t
                ->whereIn('locale', PublicPosts::languages($locale))
                ->where(fn (Builder $w) => $w->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')
                    ->orWhere('summary', 'like', '%'.addcslashes($search, '%_\\').'%'))))
            ->with(['logo', 'sector.translations'])
            ->orderByDesc('listed_at')
            ->paginate(24)
            ->withQueryString();

        return view('pages.startups.index', [
            'startups' => $startups,
            'sectors' => $sectors,
            'sector' => $sector,
            'stage' => $stage,
            'label' => $label,
            'search' => $search,
            'filtered' => $sector !== null || $stage !== null || $label !== null || $search !== '',
        ]);
    }

    public function show(string $slug): View
    {
        $locale = app()->getLocale();

        /** @var StartupTranslation|null $translation */
        $translation = StartupTranslation::query()->where('locale', $locale)->where('slug', $slug)->first();
        $startup = $translation?->startup()->listed()->with(['translations', 'logo', 'sector.translations'])->first();
        abort_if($translation === null || $startup === null || ! $translation->isLive(), 404);

        $url = route('startups.show', ['slug' => $translation->slug]);

        return view('pages.startups.show', [
            'startup' => $startup,
            'translation' => $translation,
            'body' => BodyRenderer::toHtml($translation->body),
            'sector' => $startup->sector?->name($locale) ?: null,
            'alternates' => PublicDirectory::alternates($startup, 'startups.show', 'startups.index'),
            'jsonLd' => [array_filter([
                '@type' => 'Organization',
                '@id' => $url.'#startup',
                'name' => $translation->name,
                'description' => $translation->summary,
                'url' => $startup->website ?? $url,
                'foundingDate' => $startup->founded_year !== null ? (string) $startup->founded_year : null,
                'numberOfEmployees' => $startup->team_size !== null ? ['@type' => 'QuantitativeValue', 'value' => $startup->team_size] : null,
                'areaServed' => ['@type' => 'AdministrativeArea', 'name' => __('site.region')],
            ], fn (mixed $value): bool => $value !== null)],
        ]);
    }
}
