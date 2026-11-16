<?php

namespace App\Domain\Support\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use App\Domain\Support\Enums\TicketCategory;
use App\Domain\Support\Enums\TicketPriority;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A response and resolution target in working hours (ADR-0017), for one priority and either one category or all
 * of them. The most specific policy wins; without one the defaults in config/portal.php apply.
 *
 * @property int $id
 * @property ?TicketCategory $category
 * @property TicketPriority $priority
 * @property int $response_hours
 * @property int $resolve_hours
 */
class SlaPolicy extends Model
{
    use BelongsToSite;
    use LogsActivity;

    protected $fillable = ['category', 'priority', 'response_hours', 'resolve_hours'];

    protected function casts(): array
    {
        return [
            'category' => TicketCategory::class,
            'priority' => TicketPriority::class,
            'response_hours' => 'integer',
            'resolve_hours' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('support')
            ->logOnly(['category', 'priority', 'response_hours', 'resolve_hours'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * The targets for a category and priority, in working hours.
     *
     * @return array{response: int, resolve: int}
     */
    public static function targets(TicketCategory $category, TicketPriority $priority): array
    {
        $policy = self::query()->where('priority', $priority->value)
            ->where(fn ($q) => $q->where('category', $category->value)->orWhereNull('category'))
            ->orderByRaw('category is null')
            ->first();

        if ($policy !== null) {
            return ['response' => max(1, $policy->response_hours), 'resolve' => max(1, $policy->resolve_hours)];
        }

        $default = (array) config("portal.support.sla.{$priority->value}", []);

        return ['response' => max(1, (int) ($default['response'] ?? 8)), 'resolve' => max(1, (int) ($default['resolve'] ?? 40))];
    }
}
