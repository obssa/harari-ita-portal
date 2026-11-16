<?php

namespace App\Domain\Services\Actions;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Content\Actions\WriteTranslations;
use App\Domain\Content\Models\Category;
use App\Domain\Organisation\Models\Office;
use App\Domain\Services\Enums\ServiceAudience;
use App\Domain\Services\Enums\ServiceChannel;
use App\Domain\Services\Models\ServiceItem;
use App\Domain\Translation\Support\Locales;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves a service with its languages (ADR-0016). An online service needs its https link; a service owner scoped
 * to a directorate can only file services under that directorate.
 */
final class SaveServiceItem
{
    /** Plain-text fields of a language besides the title and slug. */
    public const TEXT_FIELDS = ['summary', 'eligibility', 'documents', 'steps', 'fees', 'duration'];

    public const MAX_FAQ = 12;

    public function __construct(private readonly WriteTranslations $translations) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(ServiceItem $service, array $data, User $actor): ServiceItem
    {
        /** @var array<string, array<string, mixed>> $translations */
        $translations = (array) ($data['translations'] ?? []);
        WriteTranslations::requireName($translations, 'title');

        $channels = self::pick($data['channels'] ?? [], ServiceChannel::class);
        $audiences = self::pick($data['audiences'] ?? [], ServiceAudience::class);
        $url = filled($data['external_url'] ?? null) ? trim((string) $data['external_url']) : null;

        $errors = [];
        if ($channels === []) {
            $errors['data.channels'] = __('services.validation.channel_required');
        }
        if ($url !== null && (! str_starts_with($url, 'https://') || filter_var($url, FILTER_VALIDATE_URL) === false)) {
            $errors['data.external_url'] = __('services.validation.url_https');
        }
        if ($url === null && in_array(ServiceChannel::Online->value, $channels, true)) {
            $errors['data.external_url'] = __('services.validation.url_required');
        }

        $categoryId = filled($data['category_id'] ?? null) ? (string) $data['category_id'] : null;
        if ($categoryId !== null && Category::query()->whereKey($categoryId)->where('type', Category::TYPE_SERVICE)->doesntExist()) {
            $categoryId = null;
        }

        $officeId = filled($data['office_id'] ?? null) ? (string) $data['office_id'] : null;
        if ($officeId !== null && Office::query()->whereKey($officeId)->doesntExist()) {
            $officeId = null;
        }
        if (! OfficeScope::covers($actor, $officeId, 'services.manage')) {
            $errors['data.office_id'] = __('services.validation.office_scope');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($service, $data, $translations, $actor, $channels, $audiences, $url, $categoryId, $officeId): ServiceItem {
            $service->fill([
                'category_id' => $categoryId,
                'office_id' => $officeId,
                'channels' => $channels,
                'audiences' => $audiences,
                'external_url' => $url,
                'featured' => (bool) ($data['featured'] ?? false),
                'rank' => max(0, (int) ($data['rank'] ?? 0)),
                'reviewed_at' => filled($data['reviewed_at'] ?? null) ? CarbonImmutable::parse((string) $data['reviewed_at'])->startOfDay() : null,
            ]);
            if (! $service->exists) {
                $service->created_by = $actor->id;
            }
            $service->updated_by = $actor->id;
            $service->save();

            $this->translations->handle($service, $translations, self::TEXT_FIELDS, $actor->can('services.manage'), 'title');

            foreach (Locales::all() as $locale) {
                $translation = $service->translation($locale, fallback: false);
                if ($translation === null || ! array_key_exists($locale, $translations)) {
                    continue;
                }
                $translation->forceFill(['faq' => self::faq($translations[$locale]['faq'] ?? [])])->save();
            }

            return $service->refresh();
        });
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return list<string>
     */
    private static function pick(mixed $values, string $enum): array
    {
        $picked = [];
        foreach ((array) $values as $value) {
            $case = $enum::tryFrom((string) $value);
            if ($case !== null && ! in_array($case->value, $picked, true)) {
                $picked[] = (string) $case->value;
            }
        }

        return $picked;
    }

    /**
     * @return ?list<array{question: string, answer: string}>
     */
    private static function faq(mixed $items): ?array
    {
        $faq = [];
        foreach (is_array($items) ? $items : [] as $item) {
            $question = mb_substr(trim((string) ($item['question'] ?? '')), 0, 300);
            $answer = mb_substr(trim((string) ($item['answer'] ?? '')), 0, 2000);
            if ($question !== '' && $answer !== '') {
                $faq[] = ['question' => $question, 'answer' => $answer];
            }
        }

        return $faq === [] ? null : array_slice($faq, 0, self::MAX_FAQ);
    }
}
