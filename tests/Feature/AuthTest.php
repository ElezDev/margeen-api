<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_get_profile(): void
    {
        $this->seed();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@edwin.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'roles',
                        'permissions',
                        'company',
                    ],
                ],
            ])
            ->assertJsonPath('data.user.roles', ['admin']);

        $permissions = $response->json('data.user.permissions');
        $this->assertContains('clients.view', $permissions);
        $this->assertContains('clients.create', $permissions);
        $this->assertContains('users.manage', $permissions);

        $token = $response->json('data.access_token');

        $this->getJson('/api/auth/me', [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@edwin.com')
            ->assertJsonPath('data.company.name', 'Distribuciones Edwin')
            ->assertJsonPath('data.roles', ['admin']);
    }

    public function test_vendedor_has_limited_permissions(): void
    {
        $this->seed();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'vendedor@edwin.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.roles', ['vendedor']);

        $permissions = $response->json('data.user.permissions');

        $this->assertContains('invoices.create', $permissions);
        $this->assertNotContains('users.manage', $permissions);
        $this->assertNotContains('products.create', $permissions);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $this->seed();

        $this->postJson('/api/auth/login', [
            'email' => 'admin@edwin.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    public function test_user_can_refresh_token(): void
    {
        $this->seed();

        $login = $this->postJson('/api/auth/login', [
            'email' => 'vendedor@edwin.com',
            'password' => 'password',
        ])->assertOk();

        $this->postJson('/api/auth/refresh', [
            'refresh_token' => $login->json('data.refresh_token'),
        ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                ],
            ]);
    }
}
