<?php

namespace App\Http\Controllers\Public;

use App\Domain\Content\Support\BodyRenderer;
use App\Domain\Innovation\Models\Challenge;
use App\Domain\Innovation\Models\ChallengeTeam;
use App\Domain\Innovation\Models\ChallengeTranslation;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Challenges and hackathons (PLAN.md §4; ADR-0022): the brief, the phases with their dates, the prizes, the rules,
 * the rubric and, once staff publish them, the ranked results of the teams that agreed to be shown.
 */
final class ChallengeController extends Controller
{
    public function index(): View
    {
        $locale = app()->getLocale();
        $challenges = PublicDirectory::visible(Challenge::query(), $locale)->with('coverImage')->orderByDesc('registration_opens_at')->get();

        return view('pages.challenges.index', [
            'live' => $challenges->filter(fn (Challenge $c): bool => in_array($c->phase(), [Challenge::PHASE_UPCOMING, Challenge::PHASE_REGISTRATION, Challenge::PHASE_BUILDING], true))
                ->sortBy(fn (Challenge $c): int => $c->submission_closes_at->getTimestamp())->values(),
            'past' => $challenges->filter(fn (Challenge $c): bool => in_array($c->phase(), [Challenge::PHASE_JUDGING, Challenge::PHASE_RESULTS], true))->values(),
        ]);
    }

    public function show(string $slug): View
    {
        $locale = app()->getLocale();

        /** @var ChallengeTranslation|null $translation */
        $translation = ChallengeTranslation::query()->where('locale', $locale)->where('slug', $slug)->first();
        $challenge = $translation?->challenge()->with(['translations', 'coverImage'])->first();
        abort_if($translation === null || $challenge === null || ! $translation->isLive(), 404);

        $phase = $challenge->phase();
        $url = route('challenges.show', ['slug' => $translation->slug]);

        return view('pages.challenges.show', [
            'challenge' => $challenge,
            'translation' => $translation,
            'phase' => $phase,
            'body' => BodyRenderer::toHtml($translation->body),
            'criteria' => $challenge->criteriaIn($locale),
            'results' => $phase === Challenge::PHASE_RESULTS
                ? $challenge->teams()->whereNotNull('rank')->whereNull('erased_at')->orderBy('rank')->get(['id', 'name', 'rank', 'score', 'award', 'show_in_results'])
                    ->map(fn (ChallengeTeam $team): array => [
                        'rank' => (int) $team->rank,
                        'name' => $team->show_in_results ? $team->name : null,
                        'score' => $team->score,
                        'award' => $team->award,
                    ])->all()
                : [],
            'teams' => $challenge->teams()->whereNull('erased_at')->count(),
            'alternates' => PublicDirectory::alternates($challenge, 'challenges.show', 'challenges.index'),
            'jsonLd' => [array_filter([
                '@type' => 'Event',
                '@id' => $url.'#challenge',
                'name' => $translation->title,
                'description' => $translation->summary,
                'url' => $url,
                'eventAttendanceMode' => 'https://schema.org/MixedEventAttendanceMode',
                'eventStatus' => 'https://schema.org/EventScheduled',
                'startDate' => $challenge->registration_opens_at->toIso8601String(),
                'endDate' => $challenge->submission_closes_at->toIso8601String(),
                'organizer' => ['@id' => url('/').'#organization'],
            ], fn (mixed $value): bool => $value !== null)],
        ]);
    }
}
