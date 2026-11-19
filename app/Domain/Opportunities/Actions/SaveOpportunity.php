<?php

namespace App\Domain\Opportunities\Actions;

use App\Domain\Content\Actions\WriteTranslations;
use App\Domain\Documents\Models\Document;
use App\Domain\Forms\Models\Form;
use App\Domain\Opportunities\Enums\EmploymentType;
use App\Domain\Opportunities\Enums\OpportunityType;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\Pillar;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Saves an opportunity with its languages and documents (ADR-0032). Rules enforced here, whatever the form allowed:
 *  - it closes after it opens; a tender's bids are opened at or after the deadline;
 *  - positions and the kind of employment belong to vacancies and internships;
 *  - once the log has entries, or the opportunity is cancelled, the deadline changes only through an extension
 *    (ADR-0033), so the record of what bidders were told stays whole;
 *  - only those with `opportunities.manage` publish a language.
 */
final class SaveOpportunity
{
    public const TEXT_FIELDS = ['summary', 'eligibility', 'how_to_apply', 'outcome'];

    public const MAX_DOCUMENTS = 20;

    public function __construct(private readonly WriteTranslations $translations) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Opportunity $opportunity, array $data, User $actor): Opportunity
    {
        abort_unless($actor->can('opportunities.manage'), 403);

        /** @var array<string, array<string, mixed>> $translations */
        $translations = (array) ($data['translations'] ?? []);
        WriteTranslations::requireName($translations, 'title');

        $type = OpportunityType::tryFrom((string) ($data['type'] ?? '')) ?? $opportunity->type ?? OpportunityType::Tender;
        $opens = self::date($data['opens_at'] ?? null);
        $closes = self::date($data['closes_at'] ?? null);
        $bids = $type === OpportunityType::Tender ? self::date($data['bid_opening_at'] ?? null) : null;

        $errors = [];
        $locked = $opportunity->exists && ($opportunity->isCancelled() || $opportunity->addenda()->exists());
        if ($locked) {
            if ($closes !== null && ! $closes->equalTo($opportunity->closes_at)) {
                $errors['data.closes_at'] = __('opportunities.validation.closes_locked');
            }
            $closes = $opportunity->closes_at;
        }
        if ($closes === null) {
            $errors['data.closes_at'] = __('opportunities.validation.closes_required');
        } elseif ($opens !== null && $closes <= $opens) {
            $errors['data.closes_at'] = __('opportunities.validation.closes_after_opens');
        }
        if ($bids !== null && $closes !== null && $bids < $closes) {
            $errors['data.bid_opening_at'] = __('opportunities.validation.bids_after_closes');
        }

        $url = trim((string) ($data['apply_url'] ?? ''));
        if ($url !== '' && (! filter_var($url, FILTER_VALIDATE_URL) || ! str_starts_with(strtolower($url), 'https://'))) {
            $errors['data.apply_url'] = __('opportunities.validation.apply_url');
        }
        $email = trim((string) ($data['apply_email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['data.apply_email'] = __('opportunities.validation.apply_email');
        }

        // ADR-0053: tenders take sealed bids, never a web form.
        $formId = $type !== OpportunityType::Tender && filled($data['apply_form_id'] ?? null) ? (string) $data['apply_form_id'] : null;
        if ($formId !== null && Form::query()->whereKey($formId)->where('active', true)->doesntExist()) {
            $errors['data.apply_form_id'] = __('opportunities.validation.apply_form');
        }

        $documents = array_values(array_unique(array_map('strval', array_filter((array) ($data['documents'] ?? [])))));
        if (count($documents) > self::MAX_DOCUMENTS) {
            $errors['data.documents'] = __('opportunities.validation.documents', ['max' => self::MAX_DOCUMENTS]);
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $office = filled($data['office_id'] ?? null) && Office::query()->whereKey($data['office_id'])->exists() ? (string) $data['office_id'] : null;
        $pillar = filled($data['pillar_id'] ?? null) && Pillar::query()->whereKey($data['pillar_id'])->exists() ? (string) $data['pillar_id'] : null;
        $known = Document::query()->whereKey($documents)->pluck('id')->map(fn (mixed $id): string => (string) $id)->all();
        $documents = array_values(array_filter($documents, fn (string $id): bool => in_array($id, $known, true)));

        return DB::transaction(function () use ($opportunity, $data, $translations, $actor, $type, $opens, $closes, $bids, $url, $email, $formId, $office, $pillar, $documents): Opportunity {
            /** @var CarbonImmutable $closes */
            $opportunity->fill([
                'type' => $type,
                'reference' => filled($data['reference'] ?? null) ? mb_substr(trim((string) $data['reference']), 0, 60) : null,
                'office_id' => $office,
                'pillar_id' => $pillar,
                'opens_at' => $opens,
                'closes_at' => $closes,
                'bid_opening_at' => $bids,
                'positions' => $type->isJob() && filled($data['positions'] ?? null) ? min(1000, max(1, (int) $data['positions'])) : null,
                'employment_type' => $type === OpportunityType::Vacancy ? EmploymentType::tryFrom((string) ($data['employment_type'] ?? '')) : null,
                'apply_url' => $url !== '' ? $url : null,
                'apply_email' => $email !== '' ? strtolower($email) : null,
                'apply_form_id' => $formId,
                'featured' => (bool) ($data['featured'] ?? false),
            ]);
            // A deadline moved back into the future (before anything was logged) takes it out of the archive.
            if ($opportunity->archived_at !== null && ! $opportunity->isCancelled() && $closes->isFuture()) {
                $opportunity->archived_at = null;
            }
            if (! $opportunity->exists) {
                $opportunity->created_by = $actor->id;
            }
            $opportunity->updated_by = $actor->id;
            $opportunity->save();

            $opportunity->documents()->sync(collect($documents)->mapWithKeys(fn (string $id, int $i): array => [$id => ['sort' => $i]])->all());
            $this->translations->handle($opportunity, $translations, self::TEXT_FIELDS, true, 'title');

            return $opportunity->refresh();
        });
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->utc();
        }
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return CarbonImmutable::parse($value, 'UTC');
        } catch (Throwable) {
            return null;
        }
    }
}
