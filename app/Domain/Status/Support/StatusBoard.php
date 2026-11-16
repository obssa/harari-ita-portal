<?php

namespace App\Domain\Status\Support;

use App\Domain\Status\Enums\ComponentState;
use App\Domain\Status\Models\Incident;
use App\Domain\Status\Models\StatusComponent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * What the status page shows now (ADR-0018): each visible system with the worst impact of the incidents active on
 * it, the overall state, active incidents, upcoming maintenance and the recent history.
 */
final class StatusBoard
{
    public const HISTORY_DAYS = 30;

    private readonly CarbonImmutable $now;

    /** @var Collection<int, Incident> */
    private readonly Collection $open;

    /** @var list<array{component: StatusComponent, state: ComponentState}>|null */
    private ?array $rows = null;

    public function __construct(?CarbonImmutable $now = null)
    {
        $this->now = $now ?? CarbonImmutable::now();
        $this->open = Incident::query()->whereNull('resolved_at')->with(['components', 'updates'])->orderByDesc('started_at')->get();
    }

    /**
     * @return list<array{component: StatusComponent, state: ComponentState}>
     */
    public function components(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $active = $this->active();
        $rows = [];
        foreach (StatusComponent::query()->where('visible', true)->orderBy('sort')->orderBy('id')->get() as $component) {
            $state = ComponentState::Operational;
            foreach ($active as $incident) {
                if ($incident->components->contains('id', $component->id) && $incident->impact->severity() > $state->severity()) {
                    $state = $incident->impact;
                }
            }
            $rows[] = ['component' => $component, 'state' => $state];
        }

        return $this->rows = $rows;
    }

    public function overall(): ComponentState
    {
        $worst = ComponentState::Operational;
        foreach ($this->components() as $row) {
            if ($row['state']->severity() > $worst->severity()) {
                $worst = $row['state'];
            }
        }

        return $worst;
    }

    /**
     * @return Collection<int, Incident>
     */
    public function active(): Collection
    {
        return $this->open->filter(fn (Incident $incident): bool => $incident->isActive($this->now))->values();
    }

    /**
     * @return Collection<int, Incident>
     */
    public function upcoming(): Collection
    {
        return $this->open->filter(fn (Incident $incident): bool => $incident->isUpcoming($this->now))->sortBy('started_at')->values();
    }

    /**
     * @return Collection<int, Incident>
     */
    public function history(): Collection
    {
        return Incident::query()
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', $this->now->subDays(self::HISTORY_DAYS))
            ->with(['components', 'updates'])
            ->orderByDesc('started_at')
            ->get();
    }
}
