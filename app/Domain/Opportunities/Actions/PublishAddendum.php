<?php

namespace App\Domain\Opportunities\Actions;

use App\Domain\Documents\Models\Document;
use App\Domain\Opportunities\Enums\AddendumKind;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Opportunities\Models\OpportunityAddendum;
use App\Domain\Status\Support\LocalisedText;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Adds a numbered entry to an opportunity's log (ADR-0033), published at once:
 *  - an addendum (a change to the documents, with an optional document) needs a title;
 *  - a clarification needs the question (title) and its answer (body);
 *  - an extension sets a later deadline: the opportunity takes it, the deadline it replaced is kept on the entry,
 *    the first deadline on the opportunity, and it leaves the archive; a tender's bid opening moves by as much, so
 *    bids are never opened before the new deadline;
 *  - a cancellation needs the reason and closes the opportunity.
 * A cancelled opportunity takes no further entries.
 */
final class PublishAddendum
{
    public const TITLE_MAX = 300;

    public const BODY_MAX = 5000;

    /**
     * @param  array<string, mixed>  $data  kind, titles[locale], bodies[locale], new_closes_at, document_id
     */
    public function handle(Opportunity $opportunity, array $data, User $actor): OpportunityAddendum
    {
        abort_unless($actor->can('opportunities.manage'), 403);

        $kind = AddendumKind::tryFrom((string) ($data['kind'] ?? ''));
        $titles = LocalisedText::clean($data['titles'] ?? [], self::TITLE_MAX);
        $bodies = LocalisedText::clean($data['bodies'] ?? [], self::BODY_MAX);
        $newCloses = self::date($data['new_closes_at'] ?? null);

        $errors = [];
        if ($opportunity->isCancelled()) {
            $errors['kind'] = __('opportunities.validation.cancelled');
        }
        if ($kind === null) {
            $errors['kind'] = __('opportunities.validation.kind');
        }
        if ($titles === []) {
            $errors['titles.en'] = $kind === AddendumKind::Clarification ? __('opportunities.validation.question') : __('opportunities.validation.title');
        }
        if ($kind === AddendumKind::Clarification && $bodies === []) {
            $errors['bodies.en'] = __('opportunities.validation.answer');
        }
        if ($kind === AddendumKind::Cancellation && $bodies === []) {
            $errors['bodies.en'] = __('opportunities.validation.reason');
        }
        if ($kind === AddendumKind::Extension && ($newCloses === null || $newCloses <= $opportunity->closes_at || $newCloses->isPast())) {
            $errors['new_closes_at'] = __('opportunities.validation.extension');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $document = filled($data['document_id'] ?? null) && Document::query()->whereKey($data['document_id'])->exists() ? (string) $data['document_id'] : null;

        return DB::transaction(function () use ($opportunity, $kind, $titles, $bodies, $newCloses, $document, $actor): OpportunityAddendum {
            /** @var AddendumKind $kind */
            $locked = Opportunity::query()->whereKey($opportunity->getKey())->lockForUpdate()->firstOrFail();
            $now = CarbonImmutable::now();
            // ADR-0054: checked again on the locked row, so an entry made from a page loaded before a cancellation
            // or another extension is refused instead of reopening the opportunity or shortening its deadline.
            if ($locked->isCancelled()) {
                throw ValidationException::withMessages(['kind' => __('opportunities.validation.cancelled')]);
            }
            if ($kind === AddendumKind::Extension && ($newCloses === null || $newCloses <= $locked->closes_at)) {
                throw ValidationException::withMessages(['new_closes_at' => __('opportunities.validation.extension')]);
            }

            $addendum = new OpportunityAddendum(['kind' => $kind, 'titles' => $titles, 'bodies' => $bodies === [] ? null : $bodies, 'document_id' => $document]);
            $addendum->opportunity_id = $locked->id;
            $addendum->number = ((int) $locked->addenda()->max('number')) + 1;
            $addendum->published_at = $now;
            $addendum->created_by = $actor->id;

            if ($kind === AddendumKind::Extension) {
                $addendum->previous_closes_at = $locked->closes_at;
                $addendum->new_closes_at = $newCloses;
                $locked->original_closes_at ??= $locked->closes_at;
                if ($locked->bid_opening_at !== null) {
                    $locked->bid_opening_at = $locked->bid_opening_at->addSeconds((int) $locked->closes_at->diffInSeconds($newCloses));
                }
                $locked->closes_at = $newCloses;
                $locked->archived_at = null;
            }
            if ($kind === AddendumKind::Cancellation) {
                $locked->cancelled_at = $now;
                $locked->archived_at = $now;
            }
            $addendum->save();
            $locked->updated_by = $actor->id;
            // Saved even without a change of its own, so the public pages are purged for the new entry (ADR-0039 of the fork).
            $locked->touch();

            $opportunity->setRawAttributes($locked->getAttributes(), true);

            return $addendum;
        });
    }

    /**
     * Corrects the words or the document of an entry; its kind, number, date and deadlines stay as published, and
     * the entry shows the date of the correction.
     *
     * @param  array<string, mixed>  $data  titles[locale], bodies[locale], document_id
     */
    public function correct(OpportunityAddendum $addendum, array $data, User $actor): OpportunityAddendum
    {
        abort_unless($actor->can('opportunities.manage'), 403);
        $titles = LocalisedText::clean($data['titles'] ?? [], self::TITLE_MAX);
        $bodies = LocalisedText::clean($data['bodies'] ?? [], self::BODY_MAX);
        if ($titles === []) {
            throw ValidationException::withMessages(['titles.en' => __('opportunities.validation.title')]);
        }
        if (in_array($addendum->kind, [AddendumKind::Clarification, AddendumKind::Cancellation], true) && $bodies === []) {
            throw ValidationException::withMessages(['bodies.en' => $addendum->kind === AddendumKind::Clarification ? __('opportunities.validation.answer') : __('opportunities.validation.reason')]);
        }
        $document = filled($data['document_id'] ?? null) && Document::query()->whereKey($data['document_id'])->exists() ? (string) $data['document_id'] : null;

        $addendum->fill(['titles' => $titles, 'bodies' => $bodies === [] ? null : $bodies, 'document_id' => $document]);
        if ($addendum->isDirty()) {
            $addendum->corrected_at = CarbonImmutable::now();
            DB::transaction(function () use ($addendum, $actor): void {
                $addendum->save();
                $addendum->loadMissing('opportunity');
                $addendum->opportunity->updated_by = $actor->id;
                $addendum->opportunity->touch();
            });
        }

        return $addendum;
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
