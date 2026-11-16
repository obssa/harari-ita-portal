<?php

namespace App\Domain\People\Models;

use App\Domain\Volunteers\Models\Volunteer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A line in the history of a person the Agency deals with (a volunteer or
 * mentor today; applicants and requesters later): a note by staff, or what
 * happened (assigned, updated, status). The text is encrypted.
 *
 * @property int $id
 * @property string $notable_type
 * @property string $notable_id
 * @property ?int $user_id
 * @property string $kind
 * @property ?string $body
 * @property CarbonImmutable $created_at
 * @property-read ?User $user
 * @property-read Volunteer|Model|null $notable
 */
class PersonNote extends Model
{
    protected $table = 'people_notes';

    public const NOTE = 'note';

    public const ASSIGN = 'assign';

    public const UPDATE = 'update';

    public const REQUEST = 'request';

    public const DECISION = 'decision';

    public const STATUS = 'status';

    public const REVEAL = 'reveal';

    protected $fillable = ['user_id', 'kind', 'body'];

    protected function casts(): array
    {
        return ['body' => 'encrypted', 'created_at' => 'immutable_datetime', 'updated_at' => 'immutable_datetime'];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
