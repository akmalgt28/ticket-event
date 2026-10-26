<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $permissions = collect([
            'events.manage',
            'tickets.manage',
            'orders.manage',
            'payments.verify',
            'checkin.manage',
            'reports.view',
        ])->mapWithKeys(fn (string $permission): array => [
            $permission => Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]),
        ]);

        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $ownerRole->syncPermissions($permissions->values());
        $adminRole->syncPermissions($permissions->except('checkin.manage')->values());
        $staffRole->syncPermissions([$permissions['checkin.manage']]);

        if (app()->isProduction()) {
            return;
        }

        $owner = User::query()->where('email', 'test@example.com')->first()
            ?? User::factory()->create([
                'name' => 'Event Owner',
                'email' => 'test@example.com',
            ]);

        $owner->assignRole($ownerRole);
    }
}
