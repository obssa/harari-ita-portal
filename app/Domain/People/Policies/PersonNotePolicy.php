<?php

namespace App\Domain\People\Policies;

use App\Domain\People\Models\PersonNote;
use App\Models\User;

/**
 * The history of a person is seen with the record it belongs to and written
 * only by the actions on that record, never edited or deleted.
 */
final class PersonNotePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('volunteers.view');
    }

    public function view(User $actor, PersonNote $note): bool
    {
        return $note->notable !== null && $actor->can('view', $note->notable);
    }

    public function create(User $actor): bool
    {
        return false;
    }

    public function update(User $actor, PersonNote $note): bool
    {
        return false;
    }

    public function delete(User $actor, PersonNote $note): bool
    {
        return false;
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
