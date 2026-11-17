<?php

namespace App\Http\Controllers\Public;

use App\Domain\Api\Enums\ApiScope;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * The developers page (PLAN.md §4, ADR-0026): what the open API offers, read from its OpenAPI document so the page
 * cannot drift from the API; the scopes, how to ask for a key, the limits, what is open without a key, and a dated
 * changelog.
 */
final class DevelopersController extends Controller
{
    /** Dated changes to the API, newest first: [date, translation key]. */
    public const CHANGELOG = [
        ['2026-11-17', 'developers.changes.opportunities'],
        ['2026-11-17', 'developers.changes.datasets'],
        ['2026-11-16', 'developers.changes.v1'],
    ];

    public function __invoke(): View
    {
        /** @var array{paths?: array<string, array<string, array{tags?: list<string>, summary?: string, description?: string}>>} $document */
        $document = json_decode((string) file_get_contents(resource_path('api/openapi-v1.json')), true, flags: JSON_THROW_ON_ERROR);

        $groups = [];
        foreach ($document['paths'] ?? [] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                $tag = $operation['tags'][0] ?? 'API';
                preg_match('/scope: `([a-z_]+)`/', (string) ($operation['description'] ?? ''), $scope);
                $groups[$tag][] = [
                    'method' => strtoupper($method),
                    'path' => '/api/v1'.$path,
                    'summary' => (string) ($operation['summary'] ?? ''),
                    'scope' => $scope[1] ?? null,
                ];
            }
        }

        return view('pages.developers', [
            'groups' => $groups,
            'scopes' => ApiScope::options(),
            'rate' => (int) config('portal.api.default_rate'),
            'perPage' => (int) config('portal.api.per_page'),
            'maxPerPage' => (int) config('portal.api.max_per_page'),
            'changelog' => self::CHANGELOG,
        ]);
    }
}
