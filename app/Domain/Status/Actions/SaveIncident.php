<?php

namespace App\Domain\Status\Actions;

use App\Domain\Status\Enums\ComponentState;
use App\Domain\Status\Enums\IncidentKind;
use App\Domain\Status\Enums\IncidentStage;
use App\Domain\Status\Models\Incident;
use App\Domain\Status\Models\StatusComponent;
use App\Domain\Status\Support\LocalisedText;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Opens or edits an incident or planned maintenance (ADR-0018). A new one needs its first public update; later
 * updates go through PostIncidentUpdate.
 */
final class SaveIncident
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Incident $incident, array $data, User $actor): Incident
    {
        $kind = IncidentKind::tryFrom((string) ($data['kind'] ?? '')) ?? IncidentKind::Incident;
        $impact = ComponentState::tryFrom((string) ($data['impact'] ?? ''));
        $titles = LocalisedText::clean($data['titles'] ?? [], 200);
        $componentIds = array_values(array_unique(array_map('intval', (array) ($data['components'] ?? []))));
        $startedAt = filled($data['started_at'] ?? null) ? CarbonImmutable::parse((string) $data['started_at']) : CarbonImmutable::now();
        $bodies = LocalisedText::clean($data['bodies'] ?? [], 2000);

        $errors = [];
        if ($titles === []) {
            $errors['data.titles.en'] = __('status.validation.title');
        }
        if ($impact === null || $impact === ComponentState::Operational) {
            $errors['data.impact'] = __('status.validation.impact');
        }
        if ($componentIds === [] || StatusComponent::query()->whereKey($componentIds)->count() !== count($componentIds)) {
            $errors['data.components'] = __('status.validation.components');
        }
        if (! $incident->exists && $bodies === []) {
            $errors['data.bodies.en'] = __('status.validation.body');
        }
        if ($errors !== [] || $impact === null) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($incident, $kind, $impact, $titles, $componentIds, $startedAt, $bodies, $actor): Incident {
            $isNew = ! $incident->exists;
            $incident->fill(['kind' => $kind, 'impact' => $impact, 'titles' => $titles, 'started_at' => $startedAt]);
            if ($isNew) {
                $incident->stage = $kind === IncidentKind::Maintenance && $startedAt->isFuture() ? IncidentStage::Scheduled : IncidentStage::Investigating;
            }
            $incident->save();
            $incident->components()->sync($componentIds);

            if ($isNew) {
                $incident->updates()->create(['stage' => $incident->stage, 'bodies' => $bodies, 'author_id' => $actor->id]);
            }

            return $incident->refresh();
        });
    }
}
