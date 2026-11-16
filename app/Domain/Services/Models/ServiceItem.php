<?php

namespace App\Domain\Services\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use App\Domain\Content\Concerns\HasTranslations;
use App\Domain\Content\Concerns\TracksEditors;
use App\Domain\Content\Contracts\TranslatableModel;
use App\Domain\Content\Models\Category;
use App\Domain\Organisation\Models\Office;
use App\Domain\Services\Enums\ServiceAudience;
use App\Domain\Services\Enums\ServiceChannel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A service the Agency offers (PLAN.md §4, §5; ADR-0016): who it is for, how to use it (online, in person, by
 * phone), the responsible office and, for an online service, the link to the external e-service. The words
 * (eligibility, documents, steps, fees, time, FAQ) are per language in ServiceItemTranslation.
 *
 * @property string $id
 * @property ?string $category_id
 * @property ?string $office_id
 * @property ?list<string> $audiences
 * @property ?list<string> $channels
 * @property ?string $external_url
 * @property bool $featured
 * @property bool $proposed suggested for the Agency, not yet confirmed (ADR-0019)
 * @property int $rank
 * @property ?CarbonImmutable $reviewed_at
 * @property-read ?Category $category
 * @property-read ?Office $office
 */
class ServiceItem extends Model implements TranslatableModel
{
    use BelongsToSite;

    /** @use HasTranslations<ServiceItemTranslation> */
    use HasTranslations;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;
    use TracksEditors;

    protected $fillable = ['category_id', 'office_id', 'audiences', 'channels', 'external_url', 'featured', 'proposed', 'rank', 'reviewed_at'];

    protected $attributes = ['featured' => false, 'proposed' => false, 'rank' => 0];

    protected function casts(): array
    {
        return [
            'audiences' => 'array',
            'channels' => 'array',
            'featured' => 'boolean',
            'proposed' => 'boolean',
            'rank' => 'integer',
            'reviewed_at' => 'immutable_date',
        ];
    }

    public static function translationModel(): string
    {
        return ServiceItemTranslation::class;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('services')
            ->logOnly(['category_id', 'office_id', 'audiences', 'channels', 'external_url', 'featured', 'proposed', 'rank', 'reviewed_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /** The admin label: the title in the given language, or in any language it has. */
    public function title(?string $locale = null): string
    {
        return (string) $this->translationOrAny($locale)?->getAttribute('title');
    }

    /**
     * @return list<ServiceChannel>
     */
    public function channelList(): array
    {
        return array_values(array_filter(array_map(
            fn (mixed $value): ?ServiceChannel => ServiceChannel::tryFrom((string) $value),
            $this->channels ?? [],
        )));
    }

    /**
     * @return list<ServiceAudience>
     */
    public function audienceList(): array
    {
        return array_values(array_filter(array_map(
            fn (mixed $value): ?ServiceAudience => ServiceAudience::tryFrom((string) $value),
            $this->audiences ?? [],
        )));
    }

    public function isOnline(): bool
    {
        return in_array(ServiceChannel::Online, $this->channelList(), true) && filled($this->external_url);
    }
}
