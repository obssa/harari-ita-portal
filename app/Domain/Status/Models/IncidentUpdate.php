<?php

namespace App\Domain\Status\Models;

use App\Domain\Status\Enums\IncidentStage;
use App\Domain\Status\Support\LocalisedText;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A dated update on an incident (ADR-0018), with the stage it moved to and what to tell the public, per language.
 *
 * @property int $id
 * @property string $incident_id
 * @property IncidentStage $stage
 * @property array<string, string> $bodies
 * @property ?int $author_id
 * @property CarbonImmutable $created_at
 * @property-read Incident $incident
 */
class IncidentUpdate extends Model
{
    protected $fillable = ['stage', 'bodies', 'author_id'];

    protected function casts(): array
    {
        return ['stage' => IncidentStage::class, 'bodies' => 'array', 'created_at' => 'immutable_datetime'];
    }

    public function body(?string $locale = null): string
    {
        return LocalisedText::pick($this->bodies, $locale);
    }

    /**
     * @return BelongsTo<Incident, $this>
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
