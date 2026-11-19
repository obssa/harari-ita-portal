<?php

namespace App\Domain\Innovation\Actions;

use App\Domain\Innovation\Models\Challenge;
use App\Domain\Innovation\Models\ChallengeTeam;
use App\Domain\Innovation\Models\ChallengeTranslation;
use App\Domain\Innovation\Models\JudgeAssignment;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\People\Mail\PersonMail;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Judging a challenge (ADR-0022). Staff assign judges (users holding `judging.score`) to the teams that submitted;
 * a judge sees only those teams, may declare a conflict of interest (which removes the assignment from the
 * results) and scores each criterion from 1 to 10. A team's score is the average of its judges' weighted totals.
 * Staff then publish the results, ranked by score.
 */
final class JudgeChallenge
{
    /**
     * @param  list<int>  $judgeIds
     */
    public function assign(Challenge $challenge, array $judgeIds): int
    {
        $judges = User::permission('judging.score')->whereKey($judgeIds)->pluck('id')->all();
        if ($judges === []) {
            throw ValidationException::withMessages(['judges' => __('innovation.validation.no_judges')]);
        }

        $created = 0;
        $challenge->teams()->whereNotNull('submitted_at')->get()->each(function (ChallengeTeam $team) use ($judges, &$created): void {
            foreach ($judges as $judgeId) {
                $assignment = JudgeAssignment::query()->firstOrCreate(['challenge_team_id' => $team->id, 'user_id' => $judgeId]);
                $created += $assignment->wasRecentlyCreated ? 1 : 0;
            }
        });

        return $created;
    }

    public function declareConflict(JudgeAssignment $assignment, User $judge, string $note): JudgeAssignment
    {
        $this->own($assignment, $judge);
        $note = trim($note);
        if (mb_strlen($note) < 3) {
            throw ValidationException::withMessages(['note' => __('innovation.validation.conflict_note')]);
        }

        DB::transaction(function () use ($assignment, $note): void {
            $assignment->forceFill([
                'conflict_declared_at' => CarbonImmutable::now(),
                'conflict_note' => mb_substr($note, 0, 500),
                'scores' => null,
                'total' => null,
                'scored_at' => null,
            ])->save();
            $this->recompute($assignment->team);
        });

        return $assignment;
    }

    /**
     * @param  array<string, mixed>  $scores  criterion key => 1..10
     */
    public function score(JudgeAssignment $assignment, User $judge, array $scores, ?string $comment): JudgeAssignment
    {
        $this->own($assignment, $judge);
        if ($assignment->conflict_declared_at !== null) {
            throw ValidationException::withMessages(['scores' => __('innovation.validation.conflicted')]);
        }
        $challenge = $assignment->team->challenge;
        if ($challenge->results_published_at !== null) {
            throw ValidationException::withMessages(['scores' => __('innovation.validation.results_published')]);
        }

        $clean = [];
        $weighted = 0;
        $weights = 0;
        foreach ($challenge->criteriaIn('en') as $criterion) {
            $value = (int) ($scores[$criterion['key']] ?? 0);
            if ($value < 1 || $value > 10) {
                throw ValidationException::withMessages(["scores.{$criterion['key']}" => __('innovation.validation.judge_score')]);
            }
            $clean[$criterion['key']] = $value;
            $weighted += $value * $criterion['weight'];
            $weights += $criterion['weight'];
        }

        DB::transaction(function () use ($assignment, $clean, $weighted, $weights, $comment): void {
            $assignment->forceFill([
                'scores' => $clean,
                'total' => number_format($weights > 0 ? $weighted / $weights : 0, 2, '.', ''),
                'comment' => filled($comment) ? mb_substr(trim((string) $comment), 0, 2000) : null,
                'scored_at' => CarbonImmutable::now(),
            ])->save();
            $this->recompute($assignment->team);
        });

        return $assignment;
    }

    /** Ranks the scored teams and publishes the results; teams without a score are not ranked. */
    public function publish(Challenge $challenge): int
    {
        $teams = $challenge->teams()->whereNotNull('score')->orderByDesc('score')->orderBy('submitted_at')->get();
        if ($teams->isEmpty()) {
            throw ValidationException::withMessages(['results' => __('innovation.validation.nothing_scored')]);
        }

        $first = $challenge->results_published_at === null;
        DB::transaction(function () use ($challenge, $teams): void {
            $challenge->teams()->update(['rank' => null]);
            $teams->values()->each(fn (ChallengeTeam $team, int $i) => $team->forceFill(['rank' => $i + 1])->save());
            $challenge->forceFill(['results_published_at' => CarbonImmutable::now()])->save();
        });

        if ($first) {
            $challenge->teams()->whereNull('erased_at')->each(fn (ChallengeTeam $team) => $this->tellResults($challenge, $team));
        }

        return $teams->count();
    }

    /** ADR-0053: the team's place, if ranked, and the way to the published results, in the team's language. */
    private function tellResults(Challenge $challenge, ChallengeTeam $team): void
    {
        if (blank($team->contact_email)) {
            return;
        }
        $locale = $team->locale;
        $title = $challenge->title($locale);
        $translation = PublicDirectory::shown($challenge, $locale);
        $url = $translation instanceof ChallengeTranslation
            ? route('challenges.show', ['locale' => $locale, 'slug' => $translation->slug])
            : route('challenges.index', ['locale' => $locale]);
        $body = trim(($team->rank !== null ? __('innovation.mail.results.ranked', ['team' => $team->name, 'rank' => $team->rank], $locale).' ' : '')
            .__('innovation.mail.results.thanks', [], $locale));

        Mail::to($team->contact_email)->locale($locale)->queue(new PersonMail(
            __('innovation.mail.results.subject', ['challenge' => $title], $locale),
            __('innovation.mail.results.subject', ['challenge' => $title], $locale),
            $team->contact_name,
            $body,
            $team->reference,
            $url,
            __('innovation.mail.results.button', [], $locale),
        ));
    }

    private function recompute(ChallengeTeam $team): void
    {
        $totals = $team->assignments()->active()->whereNotNull('total')->pluck('total')->map(fn (mixed $total): float => (float) $total);
        $team->forceFill(['score' => $totals->isEmpty() ? null : number_format($totals->avg(), 2, '.', '')])->save();
    }

    private function own(JudgeAssignment $assignment, User $judge): void
    {
        if ($assignment->user_id !== $judge->id || ! $judge->can('judging.score')) {
            throw ValidationException::withMessages(['scores' => __('innovation.validation.not_your_assignment')]);
        }
    }
}
