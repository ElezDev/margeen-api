<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::values() as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        $admin = Role::findOrCreate(RoleEnum::Admin->value, 'api');
        $admin->syncPermissions(PermissionEnum::values());

        $vendedor = Role::findOrCreate(RoleEnum::Vendedor->value, 'api');
        $vendedor->syncPermissions(
            array_map(fn (PermissionEnum $permission) => $permission->value, PermissionEnum::forVendedor())
        );
    }
}
