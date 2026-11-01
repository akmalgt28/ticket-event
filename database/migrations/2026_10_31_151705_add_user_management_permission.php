<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'users.manage',
            'guard_name' => 'web',
        ]);

        Role::query()
            ->whereIn('name', ['owner', 'admin'])
            ->where('guard_name', 'web')
            ->get()
            ->each(function (Role $role) use ($permission): void {
                $role->givePermissionTo($permission);
            });
    }

    public function down(): void
    {
        $permission = Permission::query()
            ->where('name', 'users.manage')
            ->where('guard_name', 'web')
            ->first();

        if (! $permission) {
            return;
        }

        Role::query()
            ->whereIn('name', ['owner', 'admin'])
            ->where('guard_name', 'web')
            ->get()
            ->each(function (Role $role) use ($permission): void {
                $role->revokePermissionTo($permission);
            });

        $permission->delete();
    }
};
