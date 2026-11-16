<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\Category;
use App\Domain\Content\Queries\PublicPosts;
use App\Domain\Content\Support\BodyRenderer;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\Services\Enums\ServiceAudience;
use App\Domain\Services\Enums\ServiceChannel;
use App\Domain\Services\Models\ServiceItem;
use App\Domain\Services\Models\ServiceItemTranslation;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The services catalogue (PLAN.md §4; ADR-0016): search and filters by category, audience, channel and office,
 * and a page per service with who can use it, what to bring, the steps, fees, time and where to go.
 */
final class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $search = mb_substr(trim((string) $request->query('q')), 0, 80);
        $channel = ServiceChannel::tryFrom((string) $request->query('channel'));
        $audience = ServiceAudience::tryFrom((string) $request->query('audience'));
        $categories = Category::query()->where('type', Category::TYPE_SERVICE)->with('translations')->orderBy('sort')->get()
            ->filter(fn (Category $category): bool => $category->name($locale) !== '')->values();
        $category = $categories->firstWhere('id', (string) $request->query('category'));

        $services = PublicDirectory::visible(ServiceItem::query(), $locale)
            ->when($category, fn (Builder $q, Category $c) => $q->where('category_id', $c->id))
            ->when($channel, fn (Builder $q, ServiceChannel $c) => $q->whereJsonContains('channels', $c->value))
            ->when($audience, fn (Builder $q, ServiceAudience $a) => $q->whereJsonContains('audiences', $a->value))
            ->when($search !== '', fn (Builder $q) => $q->whereHas('translations', fn (Builder $t) => $t
                ->whereIn('locale', PublicPosts::languages($locale))
                ->where(fn (Builder $w) => $w
                    ->where('title', 'like', '%'.addcslashes($search, '%_\\').'%')
                    ->orWhere('summary', 'like', '%'.addcslashes($search, '%_\\').'%'))))
            ->with(['office.translations', 'category.translations'])
            ->orderByDesc('featured')
            ->orderBy('rank')
            ->paginate(24)
            ->withQueryString();

        return view('pages.services.index', [
            'services' => $services,
            'categories' => $categories,
            'category' => $category,
            'channel' => $channel,
            'audience' => $audience,
            'search' => $search,
            'filtered' => $category !== null || $channel !== null || $audience !== null || $search !== '',
        ]);
    }

    public function show(string $slug): View
    {
        $locale = app()->getLocale();

        /** @var ServiceItemTranslation|null $translation */
        $translation = ServiceItemTranslation::query()->where('locale', $locale)->where('slug', $slug)->first();
        $service = $translation?->serviceItem()->with(['translations', 'office.translations', 'category.translations'])->first();
        abort_if($translation === null || $service === null || ! $translation->isLive(), 404);

        $related = $service->category_id === null ? collect() : PublicDirectory::visible(ServiceItem::query(), $locale)
            ->where('category_id', $service->category_id)
            ->whereKeyNot($service->id)
            ->with(['office.translations', 'category.translations'])
            ->orderByDesc('featured')->orderBy('rank')
            ->limit(3)
            ->get();

        $office = $service->office;
        $url = route('services.show', ['slug' => $translation->slug]);

        return view('pages.services.show', [
            'service' => $service,
            'translation' => $translation,
            'body' => BodyRenderer::toHtml($translation->body),
            'office' => $office instanceof Office ? PublicDirectory::shown($office, $locale) : null,
            'category' => $service->category?->name($locale) ?: null,
            'related' => $related,
            'alternates' => PublicDirectory::alternates($service, 'services.show', 'services.index'),
            'jsonLd' => [array_filter([
                '@type' => 'GovernmentService',
                '@id' => $url.'#service',
                'name' => $translation->title,
                'description' => $translation->summary,
                'url' => $url,
                'serviceType' => $service->category?->name($locale) ?: null,
                'provider' => ['@id' => url('/').'#organization'],
                'areaServed' => ['@type' => 'AdministrativeArea', 'name' => __('site.region')],
                'audience' => $service->audienceList() !== [] ? array_map(fn (ServiceAudience $a): array => ['@type' => 'Audience', 'audienceType' => $a->label()], $service->audienceList()) : null,
                'availableChannel' => $service->isOnline() ? ['@type' => 'ServiceChannel', 'serviceUrl' => $service->external_url] : null,
            ], fn (mixed $value): bool => $value !== null)],
        ]);
    }
}
