<?php

namespace App\Filament\Widgets;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Dashboard\Contracts\DashboardWidget;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Opportunities\Models\OpportunityAddendum;
use App\Filament\Support\CurrentUser;
use App\Filament\Widgets\Concerns\IsDashboardWidget;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Opportunity deadlines (ADR-0037): what closes in the next seven days, soonest first, and the latest entries of the
 * logs (ADR-0033). Directorate Heads see their directorates' (ADR-0040).
 */
class OpportunityDeadlines extends Widget implements DashboardWidget
{
    use IsDashboardWidget;

    public const DAYS = 7;

    protected string $view = 'filament.widgets.opportunity-deadlines';

    public static function key(): string
    {
        return 'opportunity_deadlines';
    }

    public static function title(): string
    {
        return __('dashboard.widget.opportunity_deadlines');
    }

    public static function permission(): ?string
    {
        return 'opportunities.view';
    }

    public static function defaultSize(): int
    {
        return 6;
    }

    /**
     * @return Builder<Opportunity>
     */
    private function scoped(): Builder
    {
        $office = $this->filters()->officeId;

        return OfficeScope::apply(Opportunity::query(), CurrentUser::get(), 'opportunities.view')
            ->when($office !== null, fn (Builder $q) => $q->where('office_id', $office));
    }

    /**
     * @return Collection<int, Opportunity>
     */
    public function closing(): Collection
    {
        $now = CarbonImmutable::now();

        return $this->scoped()->with('translations')->whereNull('cancelled_at')
            ->whereBetween('closes_at', [$now, $now->addDays(self::DAYS)])->orderBy('closes_at')->limit(8)->get();
    }

    /**
     * @return Collection<int, OpportunityAddendum>
     */
    public function latestEntries(): Collection
    {
        return OpportunityAddendum::query()->whereIn('opportunity_id', $this->scoped()->select('id'))
            ->with('opportunity.translations')->orderByDesc('published_at')->limit(4)->get();
    }
}
