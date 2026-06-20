<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformRoleTest extends TestCase
{
    use RefreshDatabase;

    private function superAdminToken(): string
    {
        $this->seed();

        return JWTAuth::fromUser(
            User::query()->where('email', 'superadmin@margeen.com')->firstOrFail()
        );
    }

    public function test_super_admin_can_list_roles_and_permissions(): void
    {
        $token = $this->superAdminToken();

        $this->getJson('/api/platform/roles', $this->bearer($token))
            ->assertOk()
            ->assertJsonFragment(['name' => 'admin']);

        $this->getJson('/api/platform/permissions', $this->bearer($token))
            ->assertOk()
            ->assertJsonFragment(['name' => 'invoices.create']);
    }

    public function test_super_admin_can_create_custom_role_and_permission(): void
    {
        $token = $this->superAdminToken();

        $this->postJson('/api/platform/permissions', [
            'name' => 'inventory.view',
        ], $this->bearer($token))->assertCreated();

        $this->postJson('/api/platform/roles', [
            'name' => 'cajero',
            'permissions' => ['clients.view', 'invoices.create', 'inventory.view'],
        ], $this->bearer($token))
            ->assertCreated()
            ->assertJsonPath('data.name', 'cajero');

        $role = Role::query()->where('name', 'cajero')->firstOrFail();
        $this->assertTrue($role->hasPermissionTo('inventory.view', 'api'));
    }

    public function test_super_admin_cannot_delete_system_role(): void
    {
        $token = $this->superAdminToken();
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();

        $this->deleteJson("/api/platform/roles/{$adminRole->id}", [], $this->bearer($token))
            ->assertStatus(422);
    }
}
