<?php

namespace App\Domain\OpenData\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use App\Domain\Content\Concerns\HasTranslations;
use App\Domain\Content\Concerns\TracksEditors;
use App\Domain\Content\Contracts\TranslatableModel;
use App\Domain\OpenData\Enums\Licence;
use App\Domain\OpenData\Enums\UpdateFrequency;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\Pillar;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * An open dataset (ADR-0025): who publishes it (a directorate), its pillar, licence, how often it is updated, the
 * period it covers, keywords and an office contact address (never a person's). Its files and links are distributions.
 * The next update falls due one period after the data was last updated; an overdue dataset says so.
 *
 * @property string $id
 * @property ?string $office_id
 * @property ?string $pillar_id
 * @property Licence $licence
 * @property UpdateFrequency $frequency
 * @property ?list<string> $keywords
 * @property ?CarbonImmutable $temporal_from
 * @property ?CarbonImmutable $temporal_to
 * @property ?string $contact_email
 * @property ?CarbonImmutable $data_updated_on
 * @property bool $featured
 * @property int $download_count
 * @property ?CarbonImmutable $created_at
 * @property ?CarbonImmutable $updated_at
 * @property-read ?Office $office
 * @property-read ?Pillar $pillar
 * @property-read Collection<int, DatasetDistribution> $distributions
 */
class Dataset extends Model implements TranslatableModel
{
    use BelongsToSite;

    /** @use HasTranslations<DatasetTranslation> */
    use HasTranslations;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;
    use TracksEditors;

    protected $fillable = ['office_id', 'pillar_id', 'licence', 'frequency', 'keywords', 'temporal_from', 'temporal_to', 'contact_email', 'data_updated_on', 'featured'];

    protected $attributes = ['licence' => 'cc-by-4.0', 'frequency' => 'irregular', 'featured' => false, 'download_count' => 0];

    protected function casts(): array
    {
        return [
            'licence' => Licence::class,
            'frequency' => UpdateFrequency::class,
            'keywords' => 'array',
            'temporal_from' => 'immutable_date',
            'temporal_to' => 'immutable_date',
            'data_updated_on' => 'immutable_date',
            'featured' => 'boolean',
            'download_count' => 'integer',
        ];
    }

    public static function translationModel(): string
    {
        return DatasetTranslation::class;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('opendata')->logOnly($this->fillable)->logOnlyDirty()->dontSubmitEmptyLogs();
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
     * @return HasMany<DatasetDistribution, $this>
     */
    public function distributions(): HasMany
    {
        return $this->hasMany(DatasetDistribution::class)->orderBy('sort')->orderBy('id');
    }

    public function nextUpdateDue(): ?CarbonImmutable
    {
        return $this->data_updated_on !== null ? $this->frequency->nextDue($this->data_updated_on) : null;
    }

    public function isOverdue(?CarbonImmutable $today = null): bool
    {
        $due = $this->nextUpdateDue();

        return $due !== null && $due->lessThan($today ?? CarbonImmutable::today());
    }

    /** The name for the admin: this language, its fallbacks, or any language. */
    public function title(?string $locale = null): string
    {
        return (string) $this->translationOrAny($locale)?->getAttribute('title');
    }
}
