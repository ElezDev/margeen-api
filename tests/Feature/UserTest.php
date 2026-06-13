<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@edwin.com',
            'password' => 'password',
        ]);

        return $response->json('data.access_token');
    }

    public function test_login_returns_extended_user_profile(): void
    {
        $this->seed();

        $this->postJson('/api/auth/login', [
            'email' => 'admin@edwin.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.document', '1234567890')
            ->assertJsonPath('data.user.phone', '3001112233')
            ->assertJsonPath('data.user.notes', 'Dueño del negocio')
            ->assertJsonStructure(['data' => ['user' => ['last_login_at']]]);
    }

    public function test_user_can_update_own_profile(): void
    {
        $this->seed();
        $token = $this->loginAsAdmin();

        $this->patchJson('/api/auth/profile', [
            'phone' => '3009998877',
            'address' => 'Nueva dirección',
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('data.phone', '3009998877')
            ->assertJsonPath('data.address', 'Nueva dirección');
    }

    public function test_admin_can_create_user_with_full_profile(): void
    {
        $this->seed();
        $token = $this->loginAsAdmin();

        $this->postJson('/api/users', [
            'name' => 'Ana Vendedora',
            'email' => 'ana@edwin.com',
            'password' => 'password123',
            'document' => '1122334455',
            'phone' => '3007776655',
            'address' => 'Sampués',
            'notes' => 'Ruta sur',
            'role' => 'vendedor',
        ], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ana Vendedora')
            ->assertJsonPath('data.document', '1122334455')
            ->assertJsonPath('data.roles', ['vendedor']);
    }

    public function test_vendedor_cannot_manage_users(): void
    {
        $this->seed();

        $login = $this->postJson('/api/auth/login', [
            'email' => 'vendedor@edwin.com',
            'password' => 'password',
        ]);

        $this->getJson('/api/users', [
            'Authorization' => 'Bearer '.$login->json('data.access_token'),
        ])->assertForbidden();
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $this->seed();
        $token = $this->loginAsAdmin();
        $admin = User::query()->where('email', 'admin@edwin.com')->first();

        $this->deleteJson("/api/users/{$admin->id}", [], [
            'Authorization' => "Bearer {$token}",
        ])->assertUnprocessable();
    }
}
