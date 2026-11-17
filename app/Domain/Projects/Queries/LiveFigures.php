<?php

namespace App\Domain\Projects\Queries;

use App\Domain\Innovation\Models\Startup;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\Projects\Models\Project;
use App\Domain\Services\Enums\ServiceChannel;
use App\Domain\Services\Models\ServiceItem;
use App\Domain\Support\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;

/**
 * The figures the portal can count from its own records (ADR-0027): never typed in, so always current. Each comes
 * with what it counts and where to see the records behind it.
 */
final class LiveFigures
{
    /**
     * @return list<array{key: string, label: string, value: int, source: string, url: ?string}>
     */
    public static function for(string $locale): array
    {
        try {
            $figures = [
                ['projects', __('numbers.live.projects'), PublicDirectory::visible(Project::query(), $locale)->count(), __('numbers.source.projects'), route('projects.index')],
                ['services_online', __('numbers.live.services_online'), PublicDirectory::visible(ServiceItem::query(), $locale)
                    ->whereJsonContains('channels', ServiceChannel::Online->value)->whereNotNull('external_url')->count(), __('numbers.source.services'), route('services.index', ['channel' => ServiceChannel::Online->value])],
                ['tickets_resolved', __('numbers.live.tickets_resolved'), Ticket::query()->whereNotNull('resolved_at')
                    ->where('resolved_at', '>=', CarbonImmutable::now()->subDays(30))->count(), __('numbers.source.tickets'), null],
                ['startups', __('numbers.live.startups'), PublicDirectory::visible(Startup::query()->listed(), $locale)->count(), __('numbers.source.startups'), route('startups.index')],
                ['datasets', __('numbers.live.datasets'), PublicDirectory::visible(Dataset::query(), $locale)->count(), __('numbers.source.datasets'), route('datasets.index')],
                ['downloads', __('numbers.live.downloads'), (int) PublicDirectory::visible(Dataset::query(), $locale)->sum('download_count'), __('numbers.source.downloads'), route('datasets.index')],
            ];
        } catch (QueryException) {
            return [];
        }

        return array_map(fn (array $f): array => ['key' => $f[0], 'label' => $f[1], 'value' => (int) $f[2], 'source' => $f[3], 'url' => $f[4]], $figures);
    }
}
