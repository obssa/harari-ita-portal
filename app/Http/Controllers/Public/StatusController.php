<?php

namespace App\Http\Controllers\Public;

use App\Domain\Status\Models\Incident;
use App\Domain\Status\Models\IncidentUpdate;
use App\Domain\Status\Models\StatusComponent;
use App\Domain\Status\Support\StatusBoard;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * The service status page (PLAN.md §4; ADR-0018) with a JSON feed and RSS of incidents. Pages are cached at the
 * edge for a minute only, so a new incident shows quickly.
 */
final class StatusController extends Controller
{
    public function index(): Response
    {
        $board = new StatusBoard;

        return response()->view('pages.status.index', [
            'board' => $board,
            'overall' => $board->overall(),
        ])->header('Cache-Control', 'public, max-age=60, s-maxage=60');
    }

    public function json(): JsonResponse
    {
        $locale = app()->getLocale();
        $board = new StatusBoard;

        return response()->json([
            'page' => ['name' => __('site.name'), 'url' => route('status'), 'language' => $locale, 'updated_at' => now()->toIso8601String()],
            'status' => ['state' => $board->overall()->value, 'description' => $board->overall()->label()],
            'components' => array_map(fn (array $row): array => [
                'id' => $row['component']->id,
                'name' => $row['component']->name($locale),
                'state' => $row['state']->value,
            ], $board->components()),
            'incidents' => $board->active()->merge($board->upcoming())->merge($board->history())
                ->map(fn (Incident $incident): array => self::incident($incident, $locale))->values()->all(),
        ], 200, ['Cache-Control' => 'public, max-age=60, s-maxage=60'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function rss(): Response
    {
        $locale = app()->getLocale();
        $board = new StatusBoard;

        return response()
            ->view('feeds.status', [
                'incidents' => $board->active()->merge($board->upcoming())->merge($board->history()),
                'locale' => $locale,
            ])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=60, s-maxage=60');
    }

    /**
     * @return array<string, mixed>
     */
    private static function incident(Incident $incident, string $locale): array
    {
        return [
            'id' => $incident->id,
            'kind' => $incident->kind->value,
            'title' => $incident->title($locale),
            'impact' => $incident->impact->value,
            'stage' => $incident->stage->value,
            'started_at' => $incident->started_at->toIso8601String(),
            'resolved_at' => $incident->resolved_at?->toIso8601String(),
            'components' => $incident->components->map(fn (StatusComponent $c): int => $c->id)->values()->all(),
            'updates' => $incident->updates->map(fn (IncidentUpdate $u): array => [
                'stage' => $u->stage->value,
                'at' => $u->created_at->toIso8601String(),
                'body' => $u->body($locale),
            ])->values()->all(),
        ];
    }
}
