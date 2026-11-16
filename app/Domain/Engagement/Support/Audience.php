<?php

namespace App\Domain\Engagement\Support;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Engagement\Models\Campaign;
use App\Domain\Engagement\Models\Subscriber;
use App\Domain\Translation\Support\Locales;
use App\Domain\Volunteers\Enums\VolunteerStatus;
use App\Domain\Volunteers\Models\Volunteer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Who a campaign reaches (PLAN.md §5): confirmed subscribers of the channel by
 * topic and language; or active volunteers who have an address for the
 * channel and did not opt out. The volunteer audience needs the permission to
 * see them, over the woredas the sender looks after.
 */
final class Audience
{
    public const TYPES = ['subscribers', 'volunteers'];

    public function __construct(private readonly User $sender) {}

    /** Whether this sender may choose the audience type at all. */
    public function allowed(string $type): bool
    {
        return match ($type) {
            'subscribers' => $this->sender->can('campaigns.send') || $this->sender->can('campaigns.manage'),
            'volunteers' => $this->sender->can('volunteers.view'),
            default => false,
        };
    }

    /**
     * The audience types this sender may choose, with their labels.
     *
     * @return array<string, string>
     */
    public function options(): array
    {
        $options = [];
        foreach (self::TYPES as $type) {
            if ($this->allowed($type)) {
                $options[$type] = __("campaigns.audience.{$type}");
            }
        }

        return $options;
    }

    /** Distinct addresses the campaign would reach now. */
    public function count(Campaign $campaign): int
    {
        $query = $this->query($campaign);

        return $query === null ? 0 : (clone $query)->toBase()->distinct()->count($this->hashColumn($campaign));
    }

    /**
     * Each recipient once per address: [type, id, locale, hash]. Contact details are only read when sending.
     *
     * @return \Generator<int, array{type: string, id: string, locale: string, hash: string}>
     */
    public function recipients(Campaign $campaign): \Generator
    {
        $query = $this->query($campaign);
        if ($query === null) {
            return;
        }
        $column = $this->hashColumn($campaign);
        $type = match ($campaign->audience['type'] ?? '') {
            'volunteers' => 'volunteer',
            default => 'subscriber',
        };
        $default = Locales::default();
        $seen = [];
        foreach ($query->lazyById(500) as $person) {
            $hash = (string) $person->getAttribute($column);
            if ($hash === '' || isset($seen[$hash])) {
                continue;
            }
            $seen[$hash] = true;
            $locale = (string) ($person->getAttribute('locale') ?? $default);
            yield ['type' => $type, 'id' => (string) $person->getKey(), 'locale' => $locale, 'hash' => $hash];
        }
    }

    /**
     * @return Builder<Subscriber>|Builder<Volunteer>|null
     */
    public function query(Campaign $campaign): ?Builder
    {
        $audience = $campaign->audience;
        $type = (string) ($audience['type'] ?? '');
        if (! $this->allowed($type)) {
            return null;
        }
        $hash = $this->hashColumn($campaign);
        $woredas = array_values(array_filter((array) ($audience['woredas'] ?? [])));

        return match ($type) {
            'subscribers' => Subscriber::query()->reachable($campaign->channel)
                ->when(($audience['topics'] ?? []) !== [], fn (Builder $q) => $q->where(function (Builder $any) use ($audience): void {
                    foreach ((array) $audience['topics'] as $topic) {
                        $any->orWhereJsonContains('topics', (string) $topic);
                    }
                }))
                ->when(($audience['locales'] ?? []) !== [], fn (Builder $q) => $q->whereIn('locale', (array) $audience['locales'])),
            'volunteers' => OfficeScope::apply(Volunteer::query(), $this->sender, 'volunteers.view')
                ->whereNull('erased_at')->whereNull('campaigns_opt_out_at')->whereNotNull($hash)
                ->where('status', VolunteerStatus::Active->value)
                ->when($woredas !== [], fn (Builder $q) => $q->whereIn('woreda_id', $woredas)),
            default => null,
        };
    }

    private function hashColumn(Campaign $campaign): string
    {
        return $campaign->channel === Campaign::EMAIL ? 'email_hash' : 'phone_hash';
    }
}
