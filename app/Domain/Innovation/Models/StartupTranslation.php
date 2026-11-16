<?php

namespace App\Domain\Innovation\Models;

use App\Domain\Content\Concerns\PublishedPerLanguage;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\Translation;
use App\Domain\Content\Support\BodyRenderer;
use App\Domain\Innovation\Enums\ListingStatus;
use App\Domain\Search\Concerns\SearchableTranslation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One language of a startup's listing: its name, one-line pitch, products (one per line) and story.
 *
 * @property string $startup_id
 * @property string $name
 * @property ?string $summary
 * @property ?array<string, mixed> $body
 * @property ?string $products
 * @property ContentStatus $status
 * @property ?CarbonImmutable $published_at
 * @property-read Startup $startup
 */
class StartupTranslation extends Translation
{
    use LogsActivity;
    use PublishedPerLanguage;
    use SearchableTranslation;

    protected string $sluggable = 'name';

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return [
            'body' => 'array',
            'status' => ContentStatus::class,
            'published_at' => 'immutable_datetime',
        ];
    }

    /**
     * Searchable only while the startup is listed.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->isLive() && $this->startup()->first()?->listing_status === ListingStatus::Listed;
    }

    /**
     * @return list<?string>
     */
    public function searchableParts(): array
    {
        return [$this->name, $this->summary, $this->products, BodyRenderer::toText($this->body)];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('innovation')
            ->logOnly(['locale', 'name', 'slug', 'summary', 'products', 'status', 'published_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<Startup, $this>
     */
    public function startup(): BelongsTo
    {
        return $this->belongsTo(Startup::class);
    }

    /**
     * @return list<string>
     */
    public function productList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $this->products) ?: []), fn (string $line): bool => $line !== ''));
    }
}
