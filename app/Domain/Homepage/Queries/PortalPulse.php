<?php

namespace App\Domain\Homepage\Queries;

use App\Domain\Academy\Models\Course;
use App\Domain\Innovation\Models\Programme;
use App\Domain\Innovation\Models\Startup;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Domain\Projects\Models\Project;
use App\Domain\Services\Models\ServiceItem;
use Closure;
use Illuminate\Database\QueryException;

/**
 * The homepage's live panel (ADR-0051): what a visitor can use on the portal right now, counted from the published
 * records each time, never typed in. Only figures above zero are shown, at most four.
 */
final class PortalPulse
{
    public const MAX = 4;

    /**
     * @return list<array{key: string, value: int, label: string, url: string}>
     */
    public static function for(string $locale): array
    {
        /** @var array<string, array{Closure(): int, string}> $sources */
        $sources = [
            'services' => [fn (): int => PublicDirectory::visible(ServiceItem::query(), $locale)->count(), 'services.index'],
            'courses' => [fn (): int => PublicDirectory::visible(Course::query(), $locale)->count(), 'academy.index'],
            'programmes' => [fn (): int => PublicDirectory::visible(Programme::query(), $locale)->count(), 'programmes.index'],
            'projects' => [fn (): int => PublicDirectory::visible(Project::query(), $locale)->count(), 'projects.index'],
            'datasets' => [fn (): int => PublicDirectory::visible(Dataset::query(), $locale)->count(), 'datasets.index'],
            'startups' => [fn (): int => PublicDirectory::visible(Startup::query()->listed(), $locale)->count(), 'startups.index'],
        ];

        $figures = [];
        try {
            foreach ($sources as $key => [$count, $route]) {
                $value = $count();
                if ($value > 0) {
                    $figures[] = [
                        'key' => $key,
                        'value' => $value,
                        'label' => trans_choice("homepage.pulse.{$key}", $value, [], $locale),
                        'url' => route($route, ['locale' => $locale]),
                    ];
                }
                if (count($figures) === self::MAX) {
                    break;
                }
            }
        } catch (QueryException) {
            return [];
        }

        return $figures;
    }
}
