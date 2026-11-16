<?php

namespace App\Domain\Services\Policies;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Organisation\Policies\DirectoryPolicy;
use App\Domain\Services\Models\ServiceItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Services (PLAN.md §7): `services.view` to see them in the admin, `services.manage` to change and publish them.
 * A service owner scoped to a directorate changes only that directorate's services.
 */
final class ServiceItemPolicy extends DirectoryPolicy
{
    protected function module(): string
    {
        return 'services';
    }

    public function update(User $actor, Model $record): bool
    {
        return parent::update($actor, $record) && $this->covers($actor, $record);
    }

    public function delete(User $actor, Model $record): bool
    {
        return parent::delete($actor, $record) && $this->covers($actor, $record);
    }

    public function restore(User $actor, Model $record): bool
    {
        return parent::restore($actor, $record) && $this->covers($actor, $record);
    }

    private function covers(User $actor, Model $record): bool
    {
        return $record instanceof ServiceItem && OfficeScope::covers($actor, $record->office_id, 'services.manage');
    }
}
