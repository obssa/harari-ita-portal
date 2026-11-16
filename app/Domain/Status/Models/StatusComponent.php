<?php

namespace App\Domain\Status\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use App\Domain\Services\Models\ServiceItem;
use App\Domain\Status\Support\LocalisedText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A system shown on the status page (ADR-0018), named per language and optionally tied to a service. Its state is
 * worked out from the incidents that affect it now (StatusBoard), so planned maintenance starts and ends by itself.
 *
 * @property int $id
 * @property array<string, string> $names
 * @property ?string $service_item_id
 * @property bool $visible
 * @property int $sort
 * @property-read ?ServiceItem $serviceItem
 */
class StatusComponent extends Model
{
    use BelongsToSite;
    use LogsActivity;

    protected $fillable = ['names', 'service_item_id', 'visible', 'sort'];

    protected $attributes = ['visible' => true, 'sort' => 0];

    protected function casts(): array
    {
        return ['names' => 'array', 'visible' => 'boolean', 'sort' => 'integer'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('status')->logOnly(['names', 'service_item_id', 'visible', 'sort'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function name(?string $locale = null): string
    {
        return LocalisedText::pick($this->names, $locale);
    }

    /**
     * @return BelongsTo<ServiceItem, $this>
     */
    public function serviceItem(): BelongsTo
    {
        return $this->belongsTo(ServiceItem::class);
    }

    /**
     * @return BelongsToMany<Incident, $this>
     */
    public function incidents(): BelongsToMany
    {
        return $this->belongsToMany(Incident::class);
    }
}
