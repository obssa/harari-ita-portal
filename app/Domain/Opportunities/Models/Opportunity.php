<?php

namespace App\Domain\Opportunities\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use App\Domain\Content\Concerns\HasTranslations;
use App\Domain\Content\Concerns\TracksEditors;
use App\Domain\Content\Contracts\TranslatableModel;
use App\Domain\Documents\Models\Document;
use App\Domain\Opportunities\Enums\EmploymentType;
use App\Domain\Opportunities\Enums\OpportunityType;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\Pillar;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A tender, call for proposals, vacancy or internship (ADR-0032): who issues it, when it opens and closes, where to
 * apply, the library documents it attaches and the dated log of addenda (ADR-0033). Its state comes from the dates,
 * so a page is right at any moment; `archived_at` records when the scheduled archive saw it close.
 *
 * @property string $id
 * @property OpportunityType $type
 * @property ?string $reference
 * @property ?string $office_id
 * @property ?string $pillar_id
 * @property ?CarbonImmutable $opens_at
 * @property CarbonImmutable $closes_at
 * @property ?CarbonImmutable $original_closes_at
 * @property ?CarbonImmutable $bid_opening_at
 * @property ?int $positions
 * @property ?EmploymentType $employment_type
 * @property ?string $apply_url
 * @property ?string $apply_email
 * @property bool $featured
 * @property ?CarbonImmutable $cancelled_at
 * @property ?CarbonImmutable $archived_at
 * @property ?CarbonImmutable $created_at
 * @property ?CarbonImmutable $updated_at
 * @property-read ?Office $office
 * @property-read ?Pillar $pillar
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, OpportunityAddendum> $addenda
 */
class Opportunity extends Model implements TranslatableModel
{
    use BelongsToSite;

    /** @use HasTranslations<OpportunityTranslation> */
    use HasTranslations;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;
    use TracksEditors;

    public const STATE_UPCOMING = 'upcoming';

    public const STATE_OPEN = 'open';

    public const STATE_CLOSING = 'closing';

    public const STATE_CLOSED = 'closed';

    public const STATE_CANCELLED = 'cancelled';

    /** An open opportunity is "closing soon" in its last days. */
    public const CLOSING_DAYS = 7;

    protected $fillable = [
        'type', 'reference', 'office_id', 'pillar_id', 'opens_at', 'closes_at', 'bid_opening_at', 'positions',
        'employment_type', 'apply_url', 'apply_email', 'featured',
    ];

    protected $attributes = ['type' => 'tender', 'featured' => false];

    protected function casts(): array
    {
        return [
            'type' => OpportunityType::class,
            'employment_type' => EmploymentType::class,
            'opens_at' => 'immutable_datetime',
            'closes_at' => 'immutable_datetime',
            'original_closes_at' => 'immutable_datetime',
            'bid_opening_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
            'positions' => 'integer',
            'featured' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public static function translationModel(): string
    {
        return OpportunityTranslation::class;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('opportunities')
            ->logOnly(['type', 'reference', 'office_id', 'pillar_id', 'opens_at', 'closes_at', 'bid_opening_at', 'positions', 'employment_type', 'apply_url', 'apply_email', 'featured', 'cancelled_at', 'archived_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * @return BelongsTo<Pillar, $this>
     */
    public function pillar(): BelongsTo
    {
        return $this->belongsTo(Pillar::class);
    }

    /**
     * The notice, bid documents, terms of reference and forms, from the library, in the order staff set.
     *
     * @return BelongsToMany<Document, $this>
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class)->withPivot('sort')->orderByPivot('sort');
    }

    /**
     * The log, newest first.
     *
     * @return HasMany<OpportunityAddendum, $this>
     */
    public function addenda(): HasMany
    {
        return $this->hasMany(OpportunityAddendum::class)->orderByDesc('number');
    }

    public function title(?string $locale = null): string
    {
        return (string) $this->translationOrAny($locale)?->getAttribute('title');
    }

    public function state(?CarbonImmutable $now = null): string
    {
        $now ??= CarbonImmutable::now();

        return match (true) {
            $this->cancelled_at !== null => self::STATE_CANCELLED,
            $this->closes_at <= $now => self::STATE_CLOSED,
            $this->opens_at !== null && $this->opens_at > $now => self::STATE_UPCOMING,
            $this->closes_at <= $now->addDays(self::CLOSING_DAYS) => self::STATE_CLOSING,
            default => self::STATE_OPEN,
        };
    }

    public function isOpen(?CarbonImmutable $now = null): bool
    {
        return in_array($this->state($now), [self::STATE_OPEN, self::STATE_CLOSING], true);
    }

    /** Closed or cancelled: shown in the archive. */
    public function isPast(?CarbonImmutable $now = null): bool
    {
        return in_array($this->state($now), [self::STATE_CLOSED, self::STATE_CANCELLED], true);
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function wasExtended(): bool
    {
        return $this->original_closes_at !== null && ! $this->original_closes_at->equalTo($this->closes_at);
    }

    /** The colour family of the state on badges and pills. */
    public static function stateTone(string $state): string
    {
        return match ($state) {
            self::STATE_OPEN => 'success',
            self::STATE_CLOSING => 'warning',
            self::STATE_UPCOMING => 'cyan',
            self::STATE_CANCELLED => 'danger',
            default => 'neutral',
        };
    }

    /**
     * Not cancelled and still to close: open now or opening later.
     *
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at')->where('closes_at', '>', CarbonImmutable::now());
    }

    /**
     * Closed or cancelled.
     *
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNotNull('cancelled_at')->orWhere('closes_at', '<=', CarbonImmutable::now()));
    }
}
