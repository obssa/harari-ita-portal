<?php

namespace App\Filament\Widgets;

use App\Domain\Dashboard\Contracts\DashboardWidget;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Projects\Queries\LiveFigures;
use App\Filament\Resources\Certificates\CertificateResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Datasets\DatasetResource;
use App\Filament\Resources\Opportunities\OpportunityResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Services\ServiceItemResource;
use App\Filament\Resources\Startups\StartupResource;
use App\Filament\Resources\Tickets\TicketResource;
use App\Filament\Widgets\Concerns\IsDashboardWidget;
use Filament\Resources\Resource as FilamentResource;
use Filament\Widgets\Widget;
use Illuminate\Database\QueryException;

/**
 * Digital Harar at a glance (ADR-0037): the live figures of /numbers (ADR-0027) and the opportunities open now, each
 * counted from the records and linking to its list in the admin where the person may see it.
 */
class AtAGlance extends Widget implements DashboardWidget
{
    use IsDashboardWidget;

    protected string $view = 'filament.widgets.at-a-glance';

    /** @var array<string, class-string<FilamentResource>> */
    private const RESOURCES = [
        'projects' => ProjectResource::class,
        'services_online' => ServiceItemResource::class,
        'tickets_resolved' => TicketResource::class,
        'startups' => StartupResource::class,
        'datasets' => DatasetResource::class,
        'downloads' => DatasetResource::class,
        'learners' => CertificateResource::class,
        'courses' => CourseResource::class,
        'opportunities_open' => OpportunityResource::class,
    ];

    public static function key(): string
    {
        return 'at_a_glance';
    }

    public static function title(): string
    {
        return __('dashboard.widget.at_a_glance');
    }

    /** For those who follow the Agency's results: Admins, directorate heads, communications and analysts. */
    public static function permission(): ?string
    {
        return 'analytics.view';
    }

    public static function defaultSize(): int
    {
        return 12;
    }

    /**
     * @return list<array{key: string, label: string, value: int, url: ?string}>
     */
    public function figures(): array
    {
        $locale = app()->getLocale();
        $figures = array_map(fn (array $f): array => ['key' => $f['key'], 'label' => $f['label'], 'value' => $f['value']], LiveFigures::for($locale));
        try {
            $figures[] = ['key' => 'opportunities_open', 'label' => __('dashboard.glance.opportunities_open'), 'value' => Opportunity::query()->current()->count()];
        } catch (QueryException) {
            // The figures above are enough on their own.
        }

        return array_map(function (array $figure): array {
            $resource = self::RESOURCES[$figure['key']] ?? null;

            return [...$figure, 'url' => $resource !== null && $resource::canViewAny() ? $resource::getUrl() : null];
        }, $figures);
    }
}
