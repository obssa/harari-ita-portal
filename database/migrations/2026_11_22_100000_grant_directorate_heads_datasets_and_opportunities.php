<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * ADR-0040: Directorate Heads read the datasets and opportunities of their directorates. The permission sync only
 * grants new permissions to existing roles, so this gives the existing role the two it now has by default. Nothing is
 * taken away.
 */
return new class extends Migration
{
    public function up(): void
    {
        $role = Role::query()->where('name', 'directorate_head')->where('guard_name', 'web')->first();
        if ($role === null) {
            return;
        }
        $permissions = Permission::query()->where('guard_name', 'web')->whereIn('name', ['datasets.view', 'opportunities.view'])->pluck('name')->all();
        if ($permissions !== []) {
            $role->givePermissionTo($permissions);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        // Grants are kept: removing them could take away what a Super Admin gave on purpose.
    }
};
