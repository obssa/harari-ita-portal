<?php

namespace App\Domain\OpenData\Actions;

use App\Domain\Content\Actions\WriteTranslations;
use App\Domain\OpenData\Enums\Licence;
use App\Domain\OpenData\Enums\UpdateFrequency;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\Pillar;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Saves a dataset with its languages (ADR-0025): the publisher directorate, pillar, licence, update frequency, the
 * period covered, keywords and an office contact address. Only those with `datasets.publish` may publish a language.
 */
final class SaveDataset
{
    public const MAX_KEYWORDS = 12;

    public function __construct(private readonly WriteTranslations $translations) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Dataset $dataset, array $data, User $actor): Dataset
    {
        /** @var array<string, array<string, mixed>> $translations */
        $translations = (array) ($data['translations'] ?? []);
        WriteTranslations::requireName($translations, 'title');

        $licence = Licence::tryFrom((string) ($data['licence'] ?? Licence::CcBy->value));
        $frequency = UpdateFrequency::tryFrom((string) ($data['frequency'] ?? UpdateFrequency::Irregular->value));
        $errors = [];
        if ($licence === null) {
            $errors['data.licence'] = __('opendata.validation.licence');
        }
        if ($frequency === null) {
            $errors['data.frequency'] = __('opendata.validation.frequency');
        }

        $from = $this->date($data['temporal_from'] ?? null, 'data.temporal_from', $errors);
        $to = $this->date($data['temporal_to'] ?? null, 'data.temporal_to', $errors);
        if ($from !== null && $to !== null && $to->lessThan($from)) {
            $errors['data.temporal_to'] = __('opendata.validation.period');
        }
        $updated = $this->date($data['data_updated_on'] ?? null, 'data.data_updated_on', $errors);
        if ($updated !== null && $updated->greaterThan(CarbonImmutable::today())) {
            $errors['data.data_updated_on'] = __('opendata.validation.updated_future');
        }

        $email = filled($data['contact_email'] ?? null) ? WriteTranslations::email($data['contact_email']) : null;
        if (filled($data['contact_email'] ?? null) && $email === null) {
            $errors['data.contact_email'] = __('opendata.validation.email');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $keywords = [];
        foreach ((array) ($data['keywords'] ?? []) as $keyword) {
            $keyword = mb_substr(trim((string) $keyword), 0, 40);
            if ($keyword !== '' && ! in_array(mb_strtolower($keyword), array_map('mb_strtolower', $keywords), true)) {
                $keywords[] = $keyword;
            }
        }

        return DB::transaction(function () use ($dataset, $data, $translations, $actor, $licence, $frequency, $from, $to, $updated, $email, $keywords): Dataset {
            $dataset->fill([
                'office_id' => filled($data['office_id'] ?? null) && Office::query()->whereKey($data['office_id'])->exists() ? (string) $data['office_id'] : null,
                'pillar_id' => filled($data['pillar_id'] ?? null) && Pillar::query()->whereKey($data['pillar_id'])->exists() ? (string) $data['pillar_id'] : null,
                'licence' => $licence,
                'frequency' => $frequency,
                'keywords' => array_slice($keywords, 0, self::MAX_KEYWORDS) ?: null,
                'temporal_from' => $from,
                'temporal_to' => $to,
                'contact_email' => $email,
                'data_updated_on' => $updated,
                'featured' => (bool) ($data['featured'] ?? false),
            ]);
            $dataset->save();

            $this->translations->handle($dataset, $translations, ['summary', 'methodology', 'coverage'], $actor->can('datasets.publish'), 'title');

            return $dataset->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    private function date(mixed $value, string $field, array &$errors): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value)->startOfDay();
        } catch (Throwable) {
            $errors[$field] = __('organisation.validation.date');

            return null;
        }
    }
}
