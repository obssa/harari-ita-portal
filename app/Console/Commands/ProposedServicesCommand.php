<?php

namespace App\Console\Commands;

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Content\Models\Category;
use App\Domain\Services\Actions\SaveServiceItem;
use App\Domain\Services\Models\ServiceItem;
use App\Domain\Services\Models\ServiceItemTranslation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Loads the services proposed for the Agency (docs/proposed-services.md; ADR-0019) from
 * database/data/proposed-services.json, with their service categories, in English, Amharic and Afaan Oromo. Each is
 * marked Proposed until the Agency confirms it; staff then untick "Proposed" and fill in fees, times and offices.
 *
 * Drafts by default; `--publish` publishes them (refused in production, where the Agency confirms first).
 * Running it again adds only what is missing. `--remove` deletes the proposed services it added that are still
 * marked Proposed, and the categories left without a service.
 */
final class ProposedServicesCommand extends Command
{
    protected $signature = 'portal:proposed-services {--publish : Publish them (not in production)} {--remove : Delete the proposed services that are still unconfirmed}';

    protected $description = 'Load (or remove) the services proposed for the Agency, marked as proposed';

    public const DATA = 'database/data/proposed-services.json';

    public function handle(SaveServiceItem $save): int
    {
        /** @var array{categories: array<string, array<string, string>>, services: list<array<string, mixed>>} $data */
        $data = json_decode((string) file_get_contents(base_path(self::DATA)), true, flags: JSON_THROW_ON_ERROR);

        if ($this->option('remove')) {
            return $this->remove($data);
        }
        if ($this->option('publish') && app()->isProduction()) {
            $this->components->error('In production the Agency confirms each service before it is published.');

            return self::FAILURE;
        }

        $actor = User::role(SystemRole::SuperAdmin->value)->orderBy('id')->firstOrFail();
        $categories = $this->categories($data['categories']);
        $added = 0;

        foreach ($data['services'] as $service) {
            $title = (string) $service['en']['title'];
            if (ServiceItemTranslation::query()->where('locale', 'en')->where('title', $title)->exists()) {
                continue;
            }

            $translations = [];
            foreach (['en', 'am', 'om'] as $locale) {
                $words = (array) ($service[$locale] ?? []);
                if (($words['title'] ?? '') === '') {
                    continue;
                }
                $translations[$locale] = [
                    'title' => $words['title'],
                    'summary' => $words['summary'] ?? null,
                    'eligibility' => $words['eligibility'] ?? null,
                    'documents' => implode("\n", (array) ($words['documents'] ?? [])),
                    'steps' => implode("\n", (array) ($words['steps'] ?? [])),
                    'published' => (bool) $this->option('publish'),
                ];
            }

            $save->handle(new ServiceItem, [
                'category_id' => $categories[$service['category']] ?? null,
                'channels' => $service['channels'],
                'audiences' => $service['audiences'],
                'featured' => (bool) ($service['featured'] ?? false),
                'proposed' => true,
                'rank' => (int) ($service['rank'] ?? 0),
                'translations' => $translations,
            ], $actor);
            $added++;
        }

        $this->components->info(sprintf('%d proposed service(s) added%s; %d already there.', $added, $this->option('publish') ? ' and published' : ' as drafts', count($data['services']) - $added));

        return self::SUCCESS;
    }

    /**
     * Finds or creates each service category by its English name.
     *
     * @param  array<string, array<string, string>>  $names
     * @return array<string, string> key => category id
     */
    private function categories(array $names): array
    {
        $ids = [];
        foreach ($names as $key => $languages) {
            $existing = Category::query()->where('type', Category::TYPE_SERVICE)
                ->whereHas('translations', fn ($q) => $q->where('locale', 'en')->where('name', $languages['en']))
                ->first();
            if ($existing === null) {
                $existing = DB::transaction(function () use ($languages): Category {
                    $category = Category::query()->create(['type' => Category::TYPE_SERVICE]);
                    foreach ($languages as $locale => $name) {
                        $category->putTranslation($locale, ['name' => $name]);
                    }

                    return $category;
                });
            }
            $ids[$key] = $existing->id;
        }

        return $ids;
    }

    /**
     * @param  array{categories: array<string, array<string, string>>, services: list<array<string, mixed>>}  $data
     */
    private function remove(array $data): int
    {
        $titles = array_map(fn (array $service): string => (string) $service['en']['title'], $data['services']);
        $services = ServiceItem::query()->withTrashed()->where('proposed', true)
            ->whereHas('translations', fn ($q) => $q->where('locale', 'en')->whereIn('title', $titles))
            ->get();
        $services->each(fn (ServiceItem $service) => $service->forceDelete());

        $emptied = 0;
        foreach ($data['categories'] as $languages) {
            $category = Category::query()->where('type', Category::TYPE_SERVICE)
                ->whereHas('translations', fn ($q) => $q->where('locale', 'en')->where('name', $languages['en']))
                ->first();
            if ($category !== null && ServiceItem::query()->withTrashed()->where('category_id', $category->id)->doesntExist()) {
                $category->forceDelete();
                $emptied++;
            }
        }

        $this->components->info(sprintf('Removed %d proposed service(s) and %d empty categor%s.', $services->count(), $emptied, $emptied === 1 ? 'y' : 'ies'));

        return self::SUCCESS;
    }
}
