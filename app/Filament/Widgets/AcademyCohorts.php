<?php

namespace App\Filament\Widgets;

use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Support\CohortScope;
use App\Domain\Dashboard\Contracts\DashboardWidget;
use App\Filament\Support\CurrentUser;
use App\Filament\Widgets\Concerns\IsDashboardWidget;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The Academy's next cohorts (ADR-0037): dates, places taken of places offered and the waiting list. Instructors see
 * only the cohorts they teach (ADR-0028).
 */
class AcademyCohorts extends Widget implements DashboardWidget
{
    use IsDashboardWidget;

    protected string $view = 'filament.widgets.academy-cohorts';

    public static function key(): string
    {
        return 'academy_cohorts';
    }

    public static function title(): string
    {
        return __('dashboard.widget.academy_cohorts');
    }

    public static function permission(): ?string
    {
        return 'cohorts.view';
    }

    public static function defaultSize(): int
    {
        return 6;
    }

    /**
     * Cohorts not cancelled that have not ended, soonest first.
     *
     * @return Collection<int, CourseCohort>
     */
    public function cohorts(): Collection
    {
        /** @var Builder<CourseCohort> $query */
        $query = CohortScope::apply(CourseCohort::query(), CurrentUser::get());

        return $query->with('course.translations')->whereNull('cancelled_at')
            ->whereDate('ends_on', '>=', CarbonImmutable::today()->toDateString())
            ->withCount([
                'enrolments as enrolled_total' => fn (Builder $q) => $q->where('status', EnrolmentStatus::Enrolled->value),
                'enrolments as waiting_total' => fn (Builder $q) => $q->where('status', EnrolmentStatus::Waitlisted->value),
            ])
            ->orderBy('starts_on')->limit(6)->get();
    }
}
