<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Queries\PublicPosts;
use App\Domain\Content\Support\BodyRenderer;
use App\Domain\OpenData\Enums\DistributionFormat;
use App\Domain\OpenData\Enums\Licence;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\OpenData\Models\DatasetDistribution;
use App\Domain\OpenData\Models\DatasetTranslation;
use App\Domain\OpenData\Support\DatasetJsonLd;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\Search\Support\SearchText;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Open data (ADR-0025): the catalogue with filters and search, a dataset's page with its files, preview and licence,
 * and counted downloads. Counting stores the day and a number only; a repeat download within the hour is not counted
 * again, and robots are not counted.
 */
final class DatasetController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 80);
        $pillarId = (string) $request->query('pillar', '');
        $officeId = (string) $request->query('publisher', '');
        $format = DistributionFormat::tryFrom((string) $request->query('format', ''));
        $licence = Licence::tryFrom((string) $request->query('licence', ''));

        $base = fn (): Builder => PublicDirectory::visible(Dataset::query(), $locale);
        $datasets = $base()
            ->with(['translations', 'office.translations', 'pillar.translations', 'distributions'])
            ->when($search !== '', fn (Builder $q) => $q->whereHas('translations', fn (Builder $t) => $t
                ->whereIn('locale', PublicPosts::languages($locale))
                ->where(fn (Builder $w) => $w
                    ->where('title', 'like', '%'.addcslashes($search, '%_\\').'%')
                    ->orWhere('summary', 'like', '%'.addcslashes($search, '%_\\').'%')
                    ->orWhere('search_text', 'like', '%'.addcslashes(SearchText::normalize($search), '%_\\').'%'))))
            ->when($pillarId !== '', fn (Builder $q) => $q->where('pillar_id', $pillarId))
            ->when($officeId !== '', fn (Builder $q) => $q->where('office_id', $officeId))
            ->when($format !== null, fn (Builder $q) => $q->whereHas('distributions', fn (Builder $d) => $d->where('format', $format?->value)))
            ->when($licence !== null, fn (Builder $q) => $q->where('licence', $licence?->value))
            ->orderByDesc('featured')->orderByDesc('data_updated_on')->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('pages.data.index', [
            'datasets' => $datasets,
            'search' => $search,
            'pillars' => Pillar::query()->whereIn('id', $base()->whereNotNull('pillar_id')->select('pillar_id'))->with('translations')->orderBy('sort')->get()
                ->mapWithKeys(fn (Pillar $p): array => [$p->id => $p->name($locale)])->all(),
            'publishers' => Office::query()->whereIn('id', $base()->whereNotNull('office_id')->select('office_id'))->with('translations')->get()
                ->mapWithKeys(fn (Office $o): array => [$o->id => $o->name($locale)])->sort()->all(),
            'formats' => DistributionFormat::options(),
            'licences' => Licence::options(),
            'filters' => ['pillar' => $pillarId ?: null, 'publisher' => $officeId ?: null, 'format' => $format?->value, 'licence' => $licence?->value],
        ]);
    }

    public function show(string $slug): View
    {
        [$dataset, $translation] = $this->find($slug);
        $locale = app()->getLocale();

        return view('pages.data.show', [
            'dataset' => $dataset,
            'translation' => $translation,
            'body' => BodyRenderer::toHtml($translation->body),
            'office' => $dataset->office !== null ? PublicDirectory::shown($dataset->office, $locale) : null,
            'preview' => $dataset->distributions->first(fn (DatasetDistribution $d): bool => $d->preview !== null && $d->preview !== []),
            'alternates' => PublicDirectory::alternates($dataset, 'datasets.show', 'datasets.index'),
            'jsonLd' => [DatasetJsonLd::schemaOrg($dataset, $translation)],
        ]);
    }

    public function download(Request $request, string $slug, int $distribution): BinaryFileResponse|RedirectResponse
    {
        [$dataset, $translation] = $this->find($slug);
        /** @var DatasetDistribution|null $file */
        $file = $dataset->distributions->firstWhere('id', $distribution);
        abort_if($file === null, 404);
        if (! $file->format->isFile()) {
            return redirect()->away((string) $file->url);
        }
        $media = $file->media;
        abort_if($media === null || ! is_file($media->getPath()), 404);

        $visitor = hash('sha256', $file->id.'|'.$request->ip().'|'.(string) $request->userAgent());
        if (! DocumentController::looksLikeRobot((string) $request->userAgent()) && Cache::add("dataset-download:{$visitor}", true, 3600)) {
            // Written without the models so that a download neither changes "updated" dates nor purges the page cache.
            DB::transaction(function () use ($file, $dataset): void {
                DB::table('dataset_distributions')->where('id', $file->id)->increment('download_count');
                DB::table('datasets')->where('id', $dataset->id)->increment('download_count');
                DB::table('dataset_downloads')->upsert(
                    [['dataset_distribution_id' => $file->id, 'day' => now()->toDateString(), 'count' => 1]],
                    ['dataset_distribution_id', 'day'],
                    ['count' => DB::raw('count + 1')],
                );
            });
        }

        return response()->download($media->getPath(), $file->fileName($translation->slug), [
            'Content-Type' => (string) $file->format->mediaType(),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * The DCAT-AP catalogue of every dataset published in this language (ADR-0026), for portals that harvest it.
     */
    public function catalog(): JsonResponse
    {
        $locale = app()->getLocale();
        $datasets = PublicDirectory::visible(Dataset::query(), $locale)->with(['translations', 'distributions'])
            ->orderByDesc('data_updated_on')->limit(1000)->get();

        $entries = [];
        foreach ($datasets as $dataset) {
            $shown = PublicDirectory::shown($dataset, $locale);
            if ($shown instanceof DatasetTranslation) {
                $entries[] = DatasetJsonLd::dcat($dataset, $shown);
            }
        }

        return response()->json([
            '@context' => DatasetJsonLd::context(),
            '@id' => route('datasets.catalog'),
            '@type' => 'dcat:Catalog',
            'dct:title' => ['@value' => __('opendata.catalog_title'), '@language' => $locale],
            'dct:description' => ['@value' => __('opendata.description'), '@language' => $locale],
            'dct:publisher' => ['@id' => url('/').'#organization', '@type' => 'foaf:Organization', 'foaf:name' => __('site.name')],
            'dct:language' => $locale,
            'foaf:homepage' => ['@id' => route('datasets.index')],
            'dcat:dataset' => $entries,
        ], 200, ['Content-Type' => 'application/ld+json'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array{0: Dataset, 1: DatasetTranslation}
     */
    private function find(string $slug): array
    {
        /** @var DatasetTranslation|null $translation */
        $translation = DatasetTranslation::query()->where('locale', app()->getLocale())->where('slug', $slug)->first();
        $dataset = $translation?->dataset()->with(['translations', 'office.translations', 'pillar.translations', 'distributions.media'])->first();
        abort_if($translation === null || $dataset === null || ! $translation->isLive(), 404);

        return [$dataset, $translation];
    }
}
