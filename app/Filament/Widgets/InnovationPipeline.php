<?php

namespace App\Filament\Widgets;

use App\Domain\Dashboard\Contracts\DashboardWidget;
use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Models\Application;
use App\Domain\Innovation\Models\Challenge;
use App\Domain\Innovation\Models\Cohort;
use App\Domain\Innovation\Models\JudgeAssignment;
use App\Filament\Support\CurrentUser;
use App\Filament\Widgets\Concerns\IsDashboardWidget;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;

/**
 * The innovation pipeline (ADR-0037): the applications of the programme cohorts taking or deciding applications, by
 * stage, and for each challenge being built or judged its submissions and the scores given of those assigned.
 * Stages and counts only, never the applicants.
 */
class InnovationPipeline extends Widget implements DashboardWidget
{
    use IsDashboardWidget;

    protected string $view = 'filament.widgets.innovation-pipeline';

    public static function key(): string
    {
        return 'innovation_pipeline';
    }

    public static function title(): string
    {
        return __('dashboard.widget.innovation_pipeline');
    }

    public static function permission(): ?string
    {
        return 'applications.view';
    }

    public static function defaultSize(): int
    {
        return 6;
    }

    /**
     * Cohorts whose applications are still being decided: open, or closed in the last 90 days.
     *
     * @return list<array{cohort: Cohort, stages: array<string, int>, total: int}>
     */
    public function cohorts(): array
    {
        $since = CarbonImmutable::now()->subDays(90);
        $cohorts = Cohort::query()->with('programme.translations')->where('closes_at', '>=', $since)
            ->where('opens_at', '<=', CarbonImmutable::now())->orderBy('closes_at')->limit(4)->get();
        if ($cohorts->isEmpty()) {
            return [];
        }
        $counts = Application::query()->whereIn('cohort_id', $cohorts->modelKeys())
            ->selectRaw('cohort_id, stage, count(*) as total')->groupBy('cohort_id', 'stage')->get();

        $rows = [];
        foreach ($cohorts as $cohort) {
            $stages = [];
            foreach (ApplicationStage::cases() as $stage) {
                $stages[$stage->value] = (int) $counts->first(fn ($row): bool => (int) $row->getAttribute('cohort_id') === $cohort->id && $row->getAttribute('stage') === $stage)?->getAttribute('total');
            }
            $rows[] = ['cohort' => $cohort, 'stages' => array_filter($stages), 'total' => array_sum($stages)];
        }

        return $rows;
    }

    /**
     * Challenges being built or judged, with submissions and judging progress.
     *
     * @return list<array{challenge: Challenge, phase: string, teams: int, submitted: int, assigned: int, scored: int}>
     */
    public function challenges(): array
    {
        if (! CurrentUser::get()->can('challenges.view')) {
            return [];
        }
        $now = CarbonImmutable::now();
        $rows = [];
        foreach (Challenge::query()->with('translations')->whereNull('results_published_at')->where('registration_opens_at', '<=', $now)->orderBy('submission_closes_at')->limit(4)->get() as $challenge) {
            $teams = $challenge->teams()->whereNull('erased_at');
            $assignments = JudgeAssignment::query()->whereHas('team', fn ($q) => $q->where('challenge_id', $challenge->id))->whereNull('conflict_declared_at');
            $rows[] = [
                'challenge' => $challenge,
                'phase' => $challenge->phase($now),
                'teams' => (clone $teams)->count(),
                'submitted' => (clone $teams)->whereNotNull('submitted_at')->count(),
                'assigned' => (clone $assignments)->count(),
                'scored' => (clone $assignments)->whereNotNull('scored_at')->count(),
            ];
        }

        return $rows;
    }
}
