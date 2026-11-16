<?php

namespace App\Filament\Support;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Organisation\Enums\OfficeType;
use App\Domain\Organisation\Models\Office;

/**
 * Offices of one type by name, for the admin's select fields; with a
 * permission, only the offices the signed-in person looks after for it.
 */
final class OfficeOptions
{
    /**
     * @return array<string, string> office id => name
     */
    public static function of(OfficeType $type, ?string $permission = null): array
    {
        $query = Office::query()->where('type', $type->value)->with('translations');
        if ($permission !== null && ($ids = OfficeScope::officeIds(CurrentUser::get(), $permission)) !== null) {
            $query->whereKey($ids);
        }

        return $query->get()->mapWithKeys(fn (Office $office): array => [$office->id => $office->name()])->sortBy(fn (string $name): string => $name)->all();
    }
}
