<?php

namespace App\Domain\Innovation\Actions;

use App\Domain\Content\Actions\WriteTranslations;
use App\Domain\Innovation\Enums\ProgrammeKind;
use App\Domain\Innovation\Models\Cohort;
use App\Domain\Innovation\Models\Programme;
use App\Domain\Media\Actions\SyncMediaUsage;
use App\Domain\Status\Support\LocalisedText;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Saves a programme with its languages, its application questions and its cohorts (ADR-0021). A cohort's
 * application window must close after it opens; a cohort that already has applications is kept even when it is
 * removed from the form.
 */
final class SaveProgramme
{
    public const TEXT_FIELDS = ['summary', 'eligibility', 'benefits', 'timeline'];

    public function __construct(
        private readonly WriteTranslations $translations,
        private readonly SyncMediaUsage $usage,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Programme $programme, array $data, User $actor): Programme
    {
        /** @var array<string, array<string, mixed>> $translations */
        $translations = (array) ($data['translations'] ?? []);
        WriteTranslations::requireName($translations, 'title');

        $kind = ProgrammeKind::tryFrom((string) ($data['kind'] ?? '')) ?? ProgrammeKind::Incubation;
        $questions = $this->questions($data['questions'] ?? []);
        $cohorts = [];
        $errors = [];
        foreach (array_values((array) ($data['cohorts'] ?? [])) as $i => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $opens = filled($row['opens_at'] ?? null) ? CarbonImmutable::parse((string) $row['opens_at']) : null;
            $closes = filled($row['closes_at'] ?? null) ? CarbonImmutable::parse((string) $row['closes_at']) : null;
            if ($opens === null || $closes === null || $closes <= $opens) {
                $errors["data.cohorts.{$i}.closes_at"] = __('innovation.validation.window');

                continue;
            }
            $cohorts[] = [
                'id' => filled($row['id'] ?? null) ? (int) $row['id'] : null,
                'name' => mb_substr($name, 0, 120),
                'opens_at' => $opens,
                'closes_at' => $closes,
                'starts_on' => filled($row['starts_on'] ?? null) ? CarbonImmutable::parse((string) $row['starts_on'])->startOfDay() : null,
                'ends_on' => filled($row['ends_on'] ?? null) ? CarbonImmutable::parse((string) $row['ends_on'])->startOfDay() : null,
                'places' => filled($row['places'] ?? null) ? max(1, (int) $row['places']) : null,
            ];
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($programme, $data, $translations, $actor, $kind, $questions, $cohorts): Programme {
            $programme->fill([
                'kind' => $kind,
                'partner' => filled($data['partner'] ?? null) ? mb_substr(trim((string) $data['partner']), 0, 200) : null,
                'cover_image_id' => filled($data['cover_image_id'] ?? null) ? (int) $data['cover_image_id'] : null,
                'questions' => $questions === [] ? null : $questions,
                'sort' => max(0, (int) ($data['sort'] ?? 0)),
            ]);
            if (! $programme->exists) {
                $programme->created_by = $actor->id;
            }
            $programme->updated_by = $actor->id;
            $programme->save();

            $this->translations->handle($programme, $translations, self::TEXT_FIELDS, $actor->can('programmes.manage'), 'title');
            $this->usage->handle($programme, 'cover_image', [$programme->cover_image_id]);

            $kept = [];
            foreach ($cohorts as $row) {
                $cohort = $row['id'] !== null ? $programme->cohorts()->whereKey($row['id'])->first() : null;
                $cohort ??= new Cohort;
                $cohort->fill(collect($row)->except('id')->all());
                $programme->cohorts()->save($cohort);
                $kept[] = $cohort->id;
            }
            $programme->cohorts()->whereNotIn('id', $kept)->get()
                ->filter(fn (Cohort $cohort): bool => $cohort->applications()->doesntExist())
                ->each(fn (Cohort $cohort) => $cohort->delete());

            return $programme->refresh();
        });
    }

    /**
     * Each question keeps a stable key (from its English label, or the first language it has) so that answers stay
     * attached to it when the wording changes.
     *
     * @return list<array{key: string, labels: array<string, string>, long: bool, required: bool}>
     */
    private function questions(mixed $rows): array
    {
        $questions = [];
        $keys = [];
        foreach (array_slice(array_values(is_array($rows) ? $rows : []), 0, Programme::MAX_QUESTIONS) as $row) {
            $labels = LocalisedText::clean($row['labels'] ?? [], 300);
            if ($labels === []) {
                continue;
            }
            $key = (string) ($row['key'] ?? '');
            if (preg_match('/^[a-z0-9_]{2,40}$/', $key) !== 1) {
                $key = Str::limit(Str::slug($labels['en'] ?? reset($labels), '_'), 36, '') ?: 'question';
            }
            $base = $key;
            for ($n = 2; in_array($key, $keys, true); $n++) {
                $key = $base.'_'.$n;
            }
            $keys[] = $key;
            $questions[] = ['key' => $key, 'labels' => $labels, 'long' => (bool) ($row['long'] ?? false), 'required' => (bool) ($row['required'] ?? false)];
        }

        return $questions;
    }
}
