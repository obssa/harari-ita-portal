<?php

namespace App\Domain\Opportunities\Models;

use App\Domain\Documents\Models\Document;
use App\Domain\Opportunities\Enums\AddendumKind;
use App\Domain\Status\Support\LocalisedText;
use App\Domain\Translation\Support\Locales;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One numbered, dated entry of an opportunity's log (ADR-0033). For a clarification the title is the question and
 * the body the answer; an extension records the deadline it replaced. Entries are never deleted; a correction of
 * the text is dated.
 *
 * @property int $id
 * @property string $opportunity_id
 * @property int $number
 * @property AddendumKind $kind
 * @property array<string, string> $titles
 * @property ?array<string, string> $bodies
 * @property ?CarbonImmutable $previous_closes_at
 * @property ?CarbonImmutable $new_closes_at
 * @property ?string $document_id
 * @property CarbonImmutable $published_at
 * @property ?CarbonImmutable $corrected_at
 * @property ?int $created_by
 * @property-read Opportunity $opportunity
 * @property-read ?Document $document
 */
class OpportunityAddendum extends Model
{
    use LogsActivity;

    protected $table = 'opportunity_addenda';

    protected $fillable = ['kind', 'titles', 'bodies', 'document_id'];

    protected function casts(): array
    {
        return [
            'kind' => AddendumKind::class,
            'titles' => 'array',
            'bodies' => 'array',
            'number' => 'integer',
            'previous_closes_at' => 'immutable_datetime',
            'new_closes_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'corrected_at' => 'immutable_datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('opportunities')
            ->logOnly(['number', 'kind', 'titles', 'bodies', 'previous_closes_at', 'new_closes_at', 'document_id', 'published_at', 'corrected_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Opportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function title(?string $locale = null): string
    {
        return LocalisedText::pick($this->titles, $locale);
    }

    public function body(?string $locale = null): string
    {
        return LocalisedText::pick($this->bodies, $locale);
    }

    /** The language the title is shown in, for the `lang` attribute. */
    public function shownLocale(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        foreach (Locales::fallbackChain($locale) as $candidate) {
            if (filled($this->titles[$candidate] ?? null)) {
                return $candidate;
            }
        }

        return (string) (array_key_first(array_filter($this->titles, fn (mixed $t): bool => filled($t))) ?? $locale);
    }
}
