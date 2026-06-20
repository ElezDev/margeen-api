<?php

namespace App\Services;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PlatformRoleService
{
    /** @var list<string> */
    private array $protectedRoles = [
        RoleEnum::SuperAdmin->value,
        RoleEnum::Admin->value,
        RoleEnum::Vendedor->value,
    ];

    public function listPermissions(): Collection
    {
        return Permission::query()
            ->where('guard_name', 'api')
            ->orderBy('name')
            ->get();
    }

    public function createPermission(string $name): Permission
    {
        return Permission::findOrCreate($name, 'api');
    }

    public function deletePermission(Permission $permission): void
    {
        if (in_array($permission->name, PermissionEnum::values(), true)) {
            throw new InvalidArgumentException('No puedes eliminar permisos base del sistema.');
        }

        DB::transaction(function () use ($permission) {
            $permission->roles()->detach();
            $permission->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    public function listRoles(): Collection
    {
        return Role::query()
            ->where('guard_name', 'api')
            ->with('permissions')
            ->orderBy('name')
            ->get();
    }

    public function createRole(string $name, array $permissions = []): Role
    {
        if ($name === RoleEnum::SuperAdmin->value) {
            throw new InvalidArgumentException('No puedes crear el rol super_admin.');
        }

        $role = Role::findOrCreate($name, 'api');
        $role->syncPermissions($this->validPermissions($permissions));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role->load('permissions');
    }

    public function updateRole(Role $role, array $data): Role
    {
        if ($role->name === RoleEnum::SuperAdmin->value) {
            throw new InvalidArgumentException('No puedes modificar el rol super_admin.');
        }

        if (isset($data['name']) && $data['name'] !== $role->name) {
            if (in_array($role->name, $this->protectedRoles, true)) {
                throw new InvalidArgumentException('No puedes renombrar roles base del sistema.');
            }

            if ($data['name'] === RoleEnum::SuperAdmin->value) {
                throw new InvalidArgumentException('Nombre de rol reservado.');
            }

            $role->update(['name' => $data['name']]);
        }

        if (array_key_exists('permissions', $data)) {
            $role->syncPermissions($this->validPermissions($data['permissions'] ?? []));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role->fresh('permissions');
    }

    public function deleteRole(Role $role): void
    {
        if (in_array($role->name, $this->protectedRoles, true)) {
            throw new InvalidArgumentException('No puedes eliminar roles base del sistema.');
        }

        DB::transaction(function () use ($role) {
            $role->permissions()->detach();
            $role->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    private function validPermissions(array $permissions): array
    {
        $available = Permission::query()
            ->where('guard_name', 'api')
            ->whereIn('name', $permissions)
            ->pluck('name')
            ->all();

        return array_values($available);
    }
}
