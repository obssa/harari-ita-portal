<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Models\Category;
use App\Domain\Content\Support\BodyRenderer;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Enums\RagStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectTranslation;
use App\Domain\Projects\Models\Statistic;
use App\Domain\Projects\Queries\LiveFigures;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Projects and achievements (PLAN.md §4 Programmes & Achievements,
 * ADR-0027): the project list with filters, each project's page, and the
 * achievements page by sector with its numbers and simple charts.
 */
final class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $status = ProjectStatus::tryFrom((string) $request->query('status', ''));
        $sectorId = (string) $request->query('sector', '');
        $officeId = (string) $request->query('office', '');
        $pillarId = (string) $request->query('pillar', '');
        $rag = RagStatus::tryFrom((string) $request->query('rag', ''));

        $base = fn (): Builder => PublicDirectory::visible(Project::query(), $locale);

        $filtered = fn (): Builder => $base()
            ->when($status !== null, fn (Builder $q) => $q->where('status', $status?->value))
            ->when($sectorId !== '', fn (Builder $q) => $q->where('sector_id', $sectorId))
            ->when($officeId !== '', fn (Builder $q) => $q->where('office_id', $officeId))
            ->when($pillarId !== '', fn (Builder $q) => $q->where('pillar_id', $pillarId))
            // The RAG status is public only while a project runs (ADR-0024).
            ->when($rag !== null, fn (Builder $q) => $q->where('status', ProjectStatus::Ongoing->value)->where('rag_status', $rag?->value));

        $projects = $filtered()
            ->with(['cover', 'sector.translations', 'office.translations', 'pillar.translations'])
            ->orderByDesc('featured')->orderByDesc('starts_on')->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('pages.projects.index', [
            'projects' => $projects,
            'statuses' => ProjectStatus::options(),
            'sectors' => Category::query()->whereIn('id', $base()->whereNotNull('sector_id')->select('sector_id'))->with('translations')->get()
                ->mapWithKeys(fn (Category $c): array => [$c->id => $c->name($locale)])->sort()->all(),
            'offices' => Office::query()->whereIn('id', $base()->whereNotNull('office_id')->select('office_id'))->with('translations')->get()
                ->mapWithKeys(fn (Office $o): array => [$o->id => $o->name($locale)])->sort()->all(),
            'pillars' => Pillar::query()->whereIn('id', $base()->whereNotNull('pillar_id')->select('pillar_id'))->with('translations')->orderBy('sort')->get()
                ->mapWithKeys(fn (Pillar $p): array => [$p->id => $p->name($locale)])->all(),
            'ragOptions' => RagStatus::options(),
            'filters' => ['status' => $status?->value, 'sector' => $sectorId ?: null, 'office' => $officeId ?: null, 'pillar' => $pillarId ?: null, 'rag' => $rag?->value],
            'pins' => $this->pins($filtered(), $locale),
        ]);
    }

    public function show(string $slug): View
    {
        $locale = app()->getLocale();
        /** @var ProjectTranslation|null $translation */
        $translation = ProjectTranslation::query()->where('locale', $locale)->where('slug', $slug)->first();
        $project = $translation?->project()->with([
            'translations', 'cover', 'sector.translations', 'office.translations', 'pillar.translations', 'gallery.items.media',
            'statistics.translations', 'milestones', 'kpis.readings',
        ])->first();
        abort_if($translation === null || $project === null || ! $translation->isLive(), 404);

        $gallery = $project->gallery !== null ? PublicDirectory::shown($project->gallery, $locale) : null;
        $url = route('projects.show', ['slug' => $translation->slug]);

        return view('pages.projects.show', [
            'project' => $project,
            'translation' => $translation,
            'body' => BodyRenderer::toHtml($translation->body),
            'office' => $project->office !== null ? PublicDirectory::shown($project->office, $locale) : null,
            'galleryTranslation' => $gallery,
            'photos' => $gallery !== null ? $project->gallery->items->take(12)->values() : collect(),
            'alternates' => PublicDirectory::alternates($project, 'projects.show', 'projects.index'),
            'image' => PublicDirectory::shareImage($project->cover, 'landscape', "project-{$project->id}-{$locale}", $translation->title, __('projects.title')),
            'jsonLd' => [array_filter([
                '@type' => 'Project',
                '@id' => $url.'#project',
                'name' => $translation->title,
                'description' => $translation->summary,
                'url' => $url,
                'foundingDate' => $project->starts_on?->toDateString(),
                'parentOrganization' => ['@id' => url('/').'#organization'],
            ], fn (mixed $value): bool => $value !== null)],
        ]);
    }

    /** The achievements page became "Digital Harar in numbers" (ADR-0027). */
    public function achievements(): RedirectResponse
    {
        return redirect()->route('projects.numbers', [], 301);
    }

    /**
     * Digital Harar in numbers (ADR-0027): figures counted from the portal's records, the projects by status and
     * pillar, and the sourced statistics, region-wide and by sector.
     */
    public function numbers(): View
    {
        $locale = app()->getLocale();
        $projects = PublicDirectory::visible(Project::query(), $locale)->with(['translations', 'cover', 'sector.translations', 'pillar.translations'])->get();
        $statistics = Statistic::query()->where('on_achievements', true)->with(['translations', 'sector.translations', 'dataset.translations'])
            ->orderBy('sort')->orderBy('id')->get();

        $sectorIds = $projects->pluck('sector_id')->merge($statistics->pluck('sector_id'))->filter()->unique()->all();
        $sectors = Category::query()->whereKey($sectorIds)->where('type', Category::TYPE_SECTOR)->with('translations')->orderBy('sort')->get()
            ->map(function (Category $sector) use ($projects, $statistics, $locale): array {
                $own = $projects->where('sector_id', $sector->id);

                return [
                    'sector' => $sector,
                    'name' => $sector->name($locale),
                    'statistics' => $statistics->where('sector_id', $sector->id)->values(),
                    'counts' => self::counts($own),
                    'total' => $own->count(),
                    'completed' => $own->where('status', ProjectStatus::Completed)->sortByDesc('ends_on')->take(3)->values(),
                    'chart' => self::statusChart(self::counts($own), ['sector' => $sector->id]),
                ];
            });

        $byPillar = [];
        foreach ($projects->whereNotNull('pillar_id')->groupBy('pillar_id') as $pillarId => $group) {
            $pillar = $group->first()?->pillar;
            if ($pillar !== null) {
                $byPillar[] = ['label' => $pillar->name($locale), 'value' => $group->count(), 'url' => route('projects.index', ['pillar' => $pillarId])];
            }
        }

        return view('pages.projects.numbers', [
            'live' => LiveFigures::for($locale),
            'regional' => $statistics->whereNull('sector_id')->values(),
            'byStatus' => self::statusChart(self::counts($projects)),
            'byPillar' => $byPillar,
            'total' => $projects->count(),
            'sectors' => $sectors,
        ]);
    }

    /**
     * @param  array<string, int>  $counts
     * @param  array<string, string>  $filters
     * @return list<array{label: string, value: int, url: string}>
     */
    private static function statusChart(array $counts, array $filters = []): array
    {
        $items = [];
        foreach (ProjectStatus::cases() as $status) {
            $items[] = ['label' => $status->label(), 'value' => $counts[$status->value] ?? 0, 'url' => route('projects.index', [...$filters, 'status' => $status->value])];
        }

        return $items;
    }

    /**
     * The map pins of the projects that have one, for the locations view (ADR-0024). At most 200, newest first.
     *
     * @param  Builder<Project>  $query
     * @return list<array{title: string, url: string, lat: float, lng: float, tone: string}>
     */
    private function pins(Builder $query, string $locale): array
    {
        $pins = [];
        $projects = $query->whereNotNull('latitude')->whereNotNull('longitude')->with('translations')->latest()->limit(200)->get();
        foreach ($projects as $project) {
            $shown = PublicDirectory::shown($project, $locale);
            if ($shown === null || $project->latitude === null || $project->longitude === null) {
                continue;
            }
            $pins[] = [
                'title' => (string) $shown->getAttribute('title'),
                'url' => route('projects.show', ['locale' => $shown->locale, 'slug' => $shown->getAttribute('slug')]),
                'lat' => $project->latitude,
                'lng' => $project->longitude,
                'tone' => match ($project->publicRag()) {
                    RagStatus::Amber => 'saffron',
                    RagStatus::Red => 'ruby',
                    default => 'cyan',
                },
            ];
        }

        return $pins;
    }

    /**
     * Projects per status, in the order of the statuses.
     *
     * @param  iterable<Project>  $projects
     * @return array<string, int>
     */
    private static function counts(iterable $projects): array
    {
        $counts = array_fill_keys(array_map(fn (ProjectStatus $s): string => $s->value, ProjectStatus::cases()), 0);
        foreach ($projects as $project) {
            $counts[$project->status->value]++;
        }

        return $counts;
    }
}
