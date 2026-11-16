<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Support\BodyRenderer;
use App\Domain\Innovation\Models\Programme;
use App\Domain\Innovation\Models\ProgrammeTranslation;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Programmes (PLAN.md §4; ADR-0021): what each offers and to whom, its timeline and the cohort open now.
 */
final class ProgrammeController extends Controller
{
    public function index(): View
    {
        $locale = app()->getLocale();

        return view('pages.programmes.index', [
            'programmes' => PublicDirectory::visible(Programme::query(), $locale)->with(['cohorts', 'coverImage'])->orderBy('sort')->get(),
        ]);
    }

    public function show(string $slug): View
    {
        $locale = app()->getLocale();

        /** @var ProgrammeTranslation|null $translation */
        $translation = ProgrammeTranslation::query()->where('locale', $locale)->where('slug', $slug)->first();
        $programme = $translation?->programme()->with(['translations', 'cohorts', 'coverImage'])->first();
        abort_if($translation === null || $programme === null || ! $translation->isLive(), 404);

        $open = $programme->openCohort();
        $url = route('programmes.show', ['slug' => $translation->slug]);

        return view('pages.programmes.show', [
            'programme' => $programme,
            'translation' => $translation,
            'body' => BodyRenderer::toHtml($translation->body),
            'open' => $open,
            'next' => $open === null ? $programme->nextCohort() : null,
            'alternates' => PublicDirectory::alternates($programme, 'programmes.show', 'programmes.index'),
            'jsonLd' => [array_filter([
                '@type' => 'EducationalOccupationalProgram',
                '@id' => $url.'#programme',
                'name' => $translation->title,
                'description' => $translation->summary,
                'url' => $url,
                'provider' => ['@id' => url('/').'#organization'],
                'applicationStartDate' => $open?->opens_at->toDateString(),
                'applicationDeadline' => $open?->closes_at->toDateString(),
                'startDate' => $open?->starts_on?->toDateString(),
                'endDate' => $open?->ends_on?->toDateString(),
            ], fn (mixed $value): bool => $value !== null)],
        ]);
    }
}
