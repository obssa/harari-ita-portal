<?php

namespace App\Domain\Innovation\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use App\Domain\Content\Concerns\HasTranslations;
use App\Domain\Content\Concerns\TracksEditors;
use App\Domain\Content\Contracts\TranslatableModel;
use App\Domain\Content\Models\Category;
use App\Domain\Innovation\Enums\LabelStatus;
use App\Domain\Innovation\Enums\ListingStatus;
use App\Domain\Innovation\Enums\StartupStage;
use App\Domain\Media\Models\Media;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A startup in the directory (PLAN.md §4, §5; ADR-0020). Founders apply to be listed; an Innovation Officer reviews
 * the listing and may verify the startup-label claim. The founder's contact is encrypted, kept for the review and
 * never shown on the site. A random reference `STU-XXXX-XXXX` lets founders follow the review.
 *
 * @property string $id
 * @property string $reference
 * @property ?string $sector_id
 * @property StartupStage $stage
 * @property ?int $founded_year
 * @property ?int $team_size
 * @property ?string $area
 * @property ?string $website
 * @property ?int $logo_id
 * @property LabelStatus $label_status
 * @property ?CarbonImmutable $label_verified_at
 * @property ?int $label_verified_by
 * @property ListingStatus $listing_status
 * @property ?string $review_note
 * @property ?string $founder_name
 * @property ?string $founder_email
 * @property ?string $founder_phone
 * @property ?string $email_hash
 * @property ?string $phone_hash
 * @property ?CarbonImmutable $consented_at
 * @property ?CarbonImmutable $listed_at
 * @property ?CarbonImmutable $decided_at
 * @property CarbonImmutable $created_at
 * @property-read ?Category $sector
 * @property-read ?Media $logo
 */
class Startup extends Model implements TranslatableModel
{
    use BelongsToSite;

    /** @use HasTranslations<StartupTranslation> */
    use HasTranslations;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;
    use TracksEditors;

    public const REFERENCE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public const REFERENCE_PREFIX = 'STU';

    public const REFERENCE_PATTERN = 'STU-[2-9A-HJ-NP-Z]{4}-[2-9A-HJ-NP-Z]{4}';

    protected $fillable = [
        'reference', 'sector_id', 'stage', 'founded_year', 'team_size', 'area', 'website', 'logo_id',
        'label_status', 'listing_status', 'review_note', 'founder_name', 'founder_email', 'founder_phone',
        'email_hash', 'phone_hash', 'consented_at',
    ];

    protected $hidden = ['founder_name', 'founder_email', 'founder_phone'];

    protected $attributes = ['label_status' => 'none', 'listing_status' => 'submitted'];

    protected function casts(): array
    {
        return [
            'stage' => StartupStage::class,
            'label_status' => LabelStatus::class,
            'listing_status' => ListingStatus::class,
            'founder_name' => 'encrypted',
            'founder_email' => 'encrypted',
            'founder_phone' => 'encrypted',
            'founded_year' => 'integer',
            'team_size' => 'integer',
            'label_verified_at' => 'immutable_datetime',
            'consented_at' => 'immutable_datetime',
            'listed_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    public static function translationModel(): string
    {
        return StartupTranslation::class;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('innovation')
            ->logOnly(['sector_id', 'stage', 'founded_year', 'team_size', 'area', 'website', 'logo_id', 'label_status', 'label_verified_at', 'listing_status', 'listed_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sector_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_id');
    }

    public function name(?string $locale = null): string
    {
        return (string) $this->translationOrAny($locale)?->getAttribute('name');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeListed(Builder $query): Builder
    {
        return $query->where('listing_status', ListingStatus::Listed->value);
    }

    public static function newReference(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= self::REFERENCE_ALPHABET[random_int(0, strlen(self::REFERENCE_ALPHABET) - 1)];
            }
            $reference = self::REFERENCE_PREFIX.'-'.substr($code, 0, 4).'-'.substr($code, 4);
        } while (self::query()->withoutGlobalScopes()->where('reference', $reference)->exists());

        return $reference;
    }

    public static function normaliseReference(string $typed): string
    {
        $plain = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $typed));
        if (str_starts_with($plain, self::REFERENCE_PREFIX)) {
            $plain = substr($plain, strlen(self::REFERENCE_PREFIX));
        }

        if (strlen($plain) !== 8 || strspn($plain, self::REFERENCE_ALPHABET) !== 8) {
            return '';
        }

        return self::REFERENCE_PREFIX.'-'.substr($plain, 0, 4).'-'.substr($plain, 4);
    }
}
