<?php

namespace App\Domain\Support\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One message on a ticket (ADR-0017): a reply the requester sees, an internal note only staff see, or a message
 * from the requester. The words are encrypted.
 *
 * @property int $id
 * @property string $ticket_id
 * @property ?int $author_id
 * @property bool $from_requester
 * @property bool $internal
 * @property string $body
 * @property ?list<array{path: string, name: string, size: int, mime: string}> $files
 * @property CarbonImmutable $created_at
 * @property-read Ticket $ticket
 * @property-read ?User $author
 */
class TicketMessage extends Model
{
    protected $fillable = ['author_id', 'from_requester', 'internal', 'body', 'files'];

    protected $hidden = ['body'];

    protected $attributes = ['from_requester' => false, 'internal' => false];

    protected function casts(): array
    {
        return [
            'from_requester' => 'boolean',
            'internal' => 'boolean',
            'body' => 'encrypted',
            'files' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
