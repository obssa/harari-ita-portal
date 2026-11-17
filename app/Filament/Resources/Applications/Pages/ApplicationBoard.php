<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Models\Application;
use App\Domain\Innovation\Models\Cohort;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Support\CurrentUser;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * The applications board (PLAN.md §6 Kanban; ADR-0021, ADR-0037): one cohort's applications as a column per stage,
 * the best scored first. Cards open the application; moving it is done there, where the rules apply and the
 * decision note is written (ReviewApplication). Withdrawn applications are left out.
 */
class ApplicationBoard extends Page
{
    protected static string $resource = ApplicationResource::class;

    protected string $view = 'filament.innovation.board';

    #[Url]
    public ?int $cohort = null;

    public function mount(): void
    {
        $this->cohort ??= $this->cohorts()->first()?->id;
    }

    public function getTitle(): string
    {
        return __('innovation.admin.board');
    }

    public static function canAccess(array $parameters = []): bool
    {
        return CurrentUser::get()->can('applications.view');
    }

    /**
     * Cohorts that have opened, the latest first.
     *
     * @return EloquentCollection<int, Cohort>
     */
    public function cohorts(): EloquentCollection
    {
        return Cohort::query()->with('programme.translations')->where('opens_at', '<=', CarbonImmutable::now())
            ->orderByDesc('closes_at')->limit(30)->get();
    }

    public function selected(): ?Cohort
    {
        $cohorts = $this->cohorts();

        return $cohorts->firstWhere('id', $this->cohort) ?? $cohorts->first();
    }

    /**
     * @return array<string, Collection<int, Application>>
     */
    public function columns(): array
    {
        $cohort = $this->selected();
        $applications = $cohort === null ? collect() : Application::query()->where('cohort_id', $cohort->id)
            ->where('stage', '!=', ApplicationStage::Withdrawn->value)
            ->orderByRaw('score is null')->orderByDesc('score')->orderBy('created_at')->limit(500)->get();

        $columns = [];
        foreach (ApplicationStage::cases() as $stage) {
            if ($stage !== ApplicationStage::Withdrawn) {
                $columns[$stage->value] = $applications->where('stage', $stage)->values();
            }
        }

        return $columns;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list')
                ->label(__('innovation.admin.list'))
                ->icon(Heroicon::OutlinedListBullet)
                ->color('gray')
                ->url(ApplicationResource::getUrl('index')),
        ];
    }
}
