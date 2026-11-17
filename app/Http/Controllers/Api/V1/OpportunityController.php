<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Api\Support\ApiPresenter;
use App\Domain\Opportunities\Enums\OpportunityType;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Opportunities\Models\OpportunityTranslation;
use App\Domain\Organisation\Queries\PublicDirectory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Opportunities (scope `opportunities`, ADR-0032): tenders, calls, vacancies and internships, filtered by `type` and
 * `status` (open, by nearest deadline, or archived, newest first). One opportunity carries its documents and log.
 */
final class OpportunityController extends ApiController
{
    public const STATUSES = ['open', 'archived'];

    public function __construct(private readonly ApiPresenter $present) {}

    public function index(Request $request): JsonResponse
    {
        $locale = $this->locale();
        $typeFilter = $this->filter($request, 'type');
        $type = OpportunityType::tryFrom((string) $typeFilter);
        $status = $this->filter($request, 'status') ?? 'open';
        if ($typeFilter !== null && $type === null) {
            return response()->json(['message' => __('api.error.opportunity_type', ['types' => implode(', ', array_map(fn (OpportunityType $t): string => $t->value, OpportunityType::cases()))])], 422);
        }
        if (! in_array($status, self::STATUSES, true)) {
            return response()->json(['message' => __('api.error.opportunity_status', ['statuses' => implode(', ', self::STATUSES)])], 422);
        }

        $page = PublicDirectory::visible(Opportunity::query(), $locale)
            ->with('office.translations')
            ->when($type, fn (Builder $q, OpportunityType $t) => $q->where('type', $t->value))
            ->when($status === 'open', fn (Builder $q) => $q->current()->orderBy('closes_at'), fn (Builder $q) => $q->past()->orderByDesc('closes_at'))
            ->paginate($this->perPage($request))
            ->withQueryString();

        return $this->page($page, fn (Opportunity $opportunity): ?array => ($shown = PublicDirectory::shown($opportunity, $locale)) instanceof OpportunityTranslation
            ? $this->present->opportunity($opportunity, $shown)
            : null);
    }

    public function show(string $key): JsonResponse
    {
        [$opportunity, $shown] = $this->find(Opportunity::query()->with(['office.translations', 'documents' => fn ($q) => $q->publicAccess()->with('translations'), 'addenda']), $key);
        abort_unless($shown instanceof OpportunityTranslation, 404);

        return $this->one($this->present->opportunity($opportunity, $shown, full: true));
    }
}
