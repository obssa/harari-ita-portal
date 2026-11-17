<?php

namespace App\Domain\Innovation\Actions;

use App\Domain\Content\Actions\WriteTranslations;
use App\Domain\Innovation\Models\Challenge;
use App\Domain\Media\Actions\SyncMediaUsage;
use App\Domain\Status\Support\LocalisedText;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Saves a challenge with its languages, its phase dates and its rubric (ADR-0022). Registration must close after it
 * opens, and submissions cannot close before registration does; the rubric cannot change once a judge has scored.
 */
final class SaveChallenge
{
    public const TEXT_FIELDS = ['summary', 'prizes', 'rules'];

    public const MAX_CRITERIA = 8;

    public function __construct(
        private readonly WriteTranslations $translations,
        private readonly SyncMediaUsage $usage,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Challenge $challenge, array $data, User $actor): Challenge
    {
        /** @var array<string, array<string, mixed>> $translations */
        $translations = (array) ($data['translations'] ?? []);
        WriteTranslations::requireName($translations, 'title');

        $opens = filled($data['registration_opens_at'] ?? null) ? CarbonImmutable::parse((string) $data['registration_opens_at']) : null;
        $closes = filled($data['registration_closes_at'] ?? null) ? CarbonImmutable::parse((string) $data['registration_closes_at']) : null;
        $submissions = filled($data['submission_closes_at'] ?? null) ? CarbonImmutable::parse((string) $data['submission_closes_at']) : null;

        $errors = [];
        if ($opens === null || $closes === null || $closes <= $opens) {
            $errors['data.registration_closes_at'] = __('innovation.validation.window');
        }
        if ($submissions === null || ($closes !== null && $submissions < $closes)) {
            $errors['data.submission_closes_at'] = __('innovation.validation.submission_window');
        }
        $criteria = $this->criteria($data['criteria'] ?? []);
        if ($criteria === []) {
            $errors['data.criteria'] = __('innovation.validation.criteria');
        }
        $scored = $challenge->exists && $challenge->teams()->whereHas('assignments', fn ($q) => $q->whereNotNull('scored_at'))->exists();
        if ($scored && array_column($criteria, 'key') !== array_column($challenge->criteriaIn('en'), 'key')) {
            $errors['data.criteria'] = __('innovation.validation.criteria_locked');
        }
        if ($errors !== [] || $opens === null || $closes === null || $submissions === null) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($challenge, $data, $translations, $actor, $opens, $closes, $submissions, $criteria): Challenge {
            $challenge->fill([
                'cover_image_id' => filled($data['cover_image_id'] ?? null) ? (int) $data['cover_image_id'] : null,
                'registration_opens_at' => $opens,
                'registration_closes_at' => $closes,
                'submission_closes_at' => $submissions,
                'results_on' => filled($data['results_on'] ?? null) ? CarbonImmutable::parse((string) $data['results_on'])->startOfDay() : null,
                'max_team_size' => max(1, min(20, (int) ($data['max_team_size'] ?? 5))),
                'criteria' => $criteria,
            ]);
            if (! $challenge->exists) {
                $challenge->created_by = $actor->id;
            }
            $challenge->updated_by = $actor->id;
            $challenge->save();

            $this->translations->handle($challenge, $translations, self::TEXT_FIELDS, $actor->can('challenges.manage'), 'title');
            $this->usage->handle($challenge, 'cover_image', [$challenge->cover_image_id]);

            return $challenge->refresh();
        });
    }

    /**
     * @return list<array{key: string, labels: array<string, string>, weight: int}>
     */
    private function criteria(mixed $rows): array
    {
        $criteria = [];
        $keys = [];
        foreach (array_slice(array_values(is_array($rows) ? $rows : []), 0, self::MAX_CRITERIA) as $row) {
            $labels = LocalisedText::clean($row['labels'] ?? [], 120);
            if ($labels === []) {
                continue;
            }
            $key = (string) ($row['key'] ?? '');
            if (preg_match('/^[a-z0-9_]{2,40}$/', $key) !== 1) {
                $key = Str::limit(Str::slug($labels['en'] ?? reset($labels), '_'), 36, '') ?: 'criterion';
            }
            $base = $key;
            for ($n = 2; in_array($key, $keys, true); $n++) {
                $key = $base.'_'.$n;
            }
            $keys[] = $key;
            $criteria[] = ['key' => $key, 'labels' => $labels, 'weight' => max(1, min(100, (int) ($row['weight'] ?? 1)))];
        }

        return $criteria;
    }
}
