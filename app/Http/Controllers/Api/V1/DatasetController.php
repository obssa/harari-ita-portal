<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Api\Support\ApiPresenter;
use App\Domain\OpenData\Enums\DistributionFormat;
use App\Domain\OpenData\Enums\Licence;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\OpenData\Models\DatasetTranslation;
use App\Domain\Organisation\Queries\PublicDirectory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Open datasets (scope `datasets`, ADR-0026), filtered by `pillar`, `publisher`, `format` and `licence`. Each dataset
 * lists its files with their download addresses, which work without a key.
 */
final class DatasetController extends ApiController
{
    public function __construct(private readonly ApiPresenter $present) {}

    public function index(Request $request): JsonResponse
    {
        $locale = $this->locale();
        $format = DistributionFormat::tryFrom((string) $this->filter($request, 'format'));
        $licence = Licence::tryFrom((string) $this->filter($request, 'licence'));

        $page = PublicDirectory::visible(Dataset::query(), $locale)
            ->with(['distributions', 'office.translations'])
            ->when($this->filter($request, 'pillar'), fn (Builder $q, string $id) => $q->where('pillar_id', $id))
            ->when($this->filter($request, 'publisher'), fn (Builder $q, string $id) => $q->where('office_id', $id))
            ->when($format, fn (Builder $q, DistributionFormat $f) => $q->whereHas('distributions', fn (Builder $d) => $d->where('format', $f->value)))
            ->when($licence, fn (Builder $q, Licence $l) => $q->where('licence', $l->value))
            ->orderByDesc('data_updated_on')->orderByDesc('created_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return $this->page($page, fn (Dataset $dataset): ?array => ($shown = PublicDirectory::shown($dataset, $locale)) instanceof DatasetTranslation
            ? $this->present->dataset($dataset, $shown)
            : null);
    }

    public function show(string $key): JsonResponse
    {
        [$dataset, $shown] = $this->find(Dataset::query()->with(['distributions', 'office.translations']), $key);
        abort_unless($shown instanceof DatasetTranslation, 404);

        return $this->one($this->present->dataset($dataset, $shown, full: true));
    }
}
