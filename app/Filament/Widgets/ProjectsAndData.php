<?php

namespace App\Filament\Widgets;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Dashboard\Contracts\DashboardWidget;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Enums\RagStatus;
use App\Domain\Projects\Models\Project;
use App\Filament\Support\CurrentUser;
use App\Filament\Widgets\Concerns\IsDashboardWidget;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Projects and data (ADR-0037): the running projects of one's directorates by RAG status, with the red and amber ones
 * named, and the datasets overdue for an update. The dashboard's directorate filter narrows both.
 */
class ProjectsAndData extends Widget implements DashboardWidget
{
    use IsDashboardWidget;

    protected string $view = 'filament.widgets.projects-and-data';

    public static function key(): string
    {
        return 'projects_data';
    }

    public static function title(): string
    {
        return __('dashboard.widget.projects_data');
    }

    public static function permission(): ?string
    {
        return 'projects.view';
    }

    public static function defaultSize(): int
    {
        return 6;
    }

    /**
     * @return Builder<Project>
     */
    private function projects(): Builder
    {
        $office = $this->filters()->officeId;

        return OfficeScope::apply(Project::query(), CurrentUser::get(), 'projects.view')
            ->where('status', ProjectStatus::Ongoing->value)
            ->when($office !== null, fn (Builder $q) => $q->where('office_id', $office));
    }

    /**
     * @return array<string, int> RAG value (or "none") => running projects
     */
    public function rag(): array
    {
        $counts = $this->projects()->toBase()->selectRaw('rag_status, count(*) as total')->groupBy('rag_status')->pluck('total', 'rag_status');
        $rows = [];
        foreach (RagStatus::cases() as $rag) {
            $rows[$rag->value] = (int) ($counts[$rag->value] ?? 0);
        }
        // Running projects whose RAG status has not been set yet.
        $rows['none'] = (int) ($counts[''] ?? 0);

        return $rows;
    }

    /**
     * Running projects that are red or amber, red first.
     *
     * @return Collection<int, Project>
     */
    public function atRisk(): Collection
    {
        return $this->projects()->with('translations')->whereIn('rag_status', [RagStatus::Red->value, RagStatus::Amber->value])
            ->orderByRaw("rag_status = 'red' desc")->orderByDesc('rag_updated_at')->limit(5)->get();
    }

    /**
     * Datasets past their next update, the longest overdue first.
     *
     * @return Collection<int, Dataset>
     */
    public function overdue(): Collection
    {
        $user = CurrentUser::get();
        if (! $user->can('datasets.view')) {
            return collect();
        }
        $office = $this->filters()->officeId;

        return OfficeScope::apply(Dataset::query(), $user, 'datasets.view')->with('translations')
            ->when($office !== null, fn (Builder $q) => $q->where('office_id', $office))
            ->whereNotNull('data_updated_on')->limit(500)->get()
            ->filter(fn (Dataset $dataset): bool => $dataset->isOverdue())
            ->sortBy(fn (Dataset $dataset): string => (string) $dataset->nextUpdateDue()?->toDateString())
            ->take(5)->values();
    }
}
