<?php

namespace App\Domain\Support\Models;

use App\Domain\Content\Concerns\BelongsToSite;
use App\Domain\Organisation\Models\Office;
use App\Domain\Services\Models\ServiceItem;
use App\Domain\Support\Enums\RequesterType;
use App\Domain\Support\Enums\SlaState;
use App\Domain\Support\Enums\TicketCategory;
use App\Domain\Support\Enums\TicketChannel;
use App\Domain\Support\Enums\TicketPriority;
use App\Domain\Support\Enums\TicketStatus;
use App\Domain\Support\Sla\WorkingHours;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * An IT support request (ADR-0017) with a random reference `SUP-XXXX-XXXX`. The requester's details, the subject,
 * the description and the rating note are encrypted; the audit log records only the handling.
 *
 * @property string $id
 * @property string $reference
 * @property string $locale
 * @property RequesterType $requester_type
 * @property ?string $requester_office
 * @property ?string $name
 * @property ?string $email
 * @property ?string $phone
 * @property ?string $email_hash
 * @property ?string $phone_hash
 * @property ?string $office_id
 * @property ?string $service_item_id
 * @property TicketCategory $category
 * @property TicketPriority $priority
 * @property TicketStatus $status
 * @property TicketChannel $channel
 * @property string $subject
 * @property string $description
 * @property ?list<array{path: string, name: string, size: int, mime: string}> $files
 * @property ?int $assignee_id
 * @property ?CarbonImmutable $response_due_at
 * @property ?CarbonImmutable $resolve_due_at
 * @property ?CarbonImmutable $first_response_at
 * @property ?CarbonImmutable $resolved_at
 * @property ?CarbonImmutable $closed_at
 * @property ?int $satisfaction
 * @property ?string $satisfaction_note
 * @property ?CarbonImmutable $erased_at
 * @property CarbonImmutable $created_at
 * @property-read ?Office $office
 * @property-read ?ServiceItem $serviceItem
 * @property-read ?User $assignee
 * @property-read Collection<int, TicketMessage> $messages
 */
class Ticket extends Model
{
    use BelongsToSite;
    use HasUlids;
    use LogsActivity;

    /** Letters and digits that cannot be mistaken for each other. */
    public const REFERENCE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public const REFERENCE_PREFIX = 'SUP';

    public const REFERENCE_PATTERN = 'SUP-[2-9A-HJ-NP-Z]{4}-[2-9A-HJ-NP-Z]{4}';

    /** Working minutes before a target at which a ticket counts as due soon. */
    public const DUE_SOON_MINUTES = 120;

    protected $fillable = [
        'locale', 'reference', 'requester_type', 'requester_office', 'name', 'email', 'phone', 'email_hash', 'phone_hash',
        'office_id', 'service_item_id', 'category', 'priority', 'status', 'channel', 'subject', 'description',
        'response_due_at', 'resolve_due_at',
    ];

    protected $hidden = ['name', 'email', 'phone', 'subject', 'description', 'satisfaction_note'];

    protected $attributes = ['status' => 'new', 'priority' => 'normal', 'channel' => 'web'];

    protected function casts(): array
    {
        return [
            'requester_type' => RequesterType::class,
            'name' => 'encrypted',
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'subject' => 'encrypted',
            'description' => 'encrypted',
            'satisfaction_note' => 'encrypted',
            'category' => TicketCategory::class,
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'channel' => TicketChannel::class,
            'files' => 'array',
            'satisfaction' => 'integer',
            'response_due_at' => 'immutable_datetime',
            'resolve_due_at' => 'immutable_datetime',
            'first_response_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
            'erased_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('support')
            ->logOnly(['status', 'priority', 'category', 'office_id', 'assignee_id', 'first_response_at', 'resolved_at', 'closed_at', 'satisfaction'])
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
     * @return BelongsTo<ServiceItem, $this>
     */
    public function serviceItem(): BelongsTo
    {
        return $this->belongsTo(ServiceItem::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return HasMany<TicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('id');
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

    /** A reference as people type it: any case, spaces or missing dashes; '' when it cannot be one. */
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

    /** Against the resolution target. */
    public function slaState(?CarbonImmutable $now = null): SlaState
    {
        return self::state($this->resolve_due_at, $this->resolved_at ?? ($this->status->isOpen() ? null : $this->closed_at), $now);
    }

    /** Against the first-response target. */
    public function responseState(?CarbonImmutable $now = null): SlaState
    {
        return self::state($this->response_due_at, $this->first_response_at, $now);
    }

    private static function state(?CarbonImmutable $due, ?CarbonImmutable $done, ?CarbonImmutable $now): SlaState
    {
        if ($due === null) {
            return $done !== null ? SlaState::Met : SlaState::OnTime;
        }
        if ($done !== null) {
            return $done <= $due ? SlaState::Met : SlaState::Breached;
        }

        $now ??= CarbonImmutable::now();
        if ($now >= $due) {
            return SlaState::Breached;
        }

        return WorkingHours::minutesBetween($now, $due) <= self::DUE_SOON_MINUTES ? SlaState::DueSoon : SlaState::OnTime;
    }
}
