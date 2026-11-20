<?php

namespace App\Domain\Status\Actions;

use App\Domain\Status\Enums\IncidentStage;
use App\Domain\Status\Models\Incident;
use App\Domain\Status\Models\IncidentUpdate;
use App\Domain\Status\Support\LocalisedText;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A dated public update on an incident (ADR-0018). Moving to "resolved" ends it; writing again on a resolved
 * incident reopens it. The stage moves only as IncidentStage::nextStages() allows, decided on the locked incident
 * (ADR-0054), so an ongoing or resolved incident is never shown as scheduled again.
 */
final class PostIncidentUpdate
{
    /**
     * @param  array<string, mixed>  $bodies
     */
    public function handle(Incident $incident, IncidentStage $stage, array $bodies, User $actor): IncidentUpdate
    {
        $clean = LocalisedText::clean($bodies, 2000);
        if ($clean === []) {
            throw ValidationException::withMessages(['bodies.en' => __('status.validation.body')]);
        }

        return DB::transaction(function () use ($incident, $stage, $clean, $actor): IncidentUpdate {
            $locked = Incident::query()->whereKey($incident->getKey())->lockForUpdate()->firstOrFail();
            $incident->setRawAttributes($locked->getAttributes(), true);
            if (! $incident->stage->canMoveTo($stage)) {
                throw ValidationException::withMessages(['stage' => __('status.validation.stage_move', ['stage' => $incident->stage->label()])]);
            }

            // A further update on a resolved incident keeps the time it was resolved.
            $resolvedAt = $incident->stage === IncidentStage::Resolved ? $incident->resolved_at : CarbonImmutable::now();
            $incident->stage = $stage;
            $incident->resolved_at = $stage === IncidentStage::Resolved ? $resolvedAt : null;
            $incident->save();

            return $incident->updates()->create(['stage' => $stage, 'bodies' => $clean, 'author_id' => $actor->id]);
        });
    }
}
