<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(string $email): string
    {
        return JWTAuth::fromUser(
            User::query()->where('email', $email)->firstOrFail()
        );
    }

    public function test_vendedor_can_list_and_search_clients(): void
    {
        $this->seed();
        $token = $this->tokenFor('vendedor@edwin.com');

        $this->getJson('/api/clients', $this->bearer($token))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Edwin Pérez');

        $this->getJson('/api/clients?q=edwin', $this->bearer($token))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/clients?q=zzzzz', $this->bearer($token))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_vendedor_can_create_client(): void
    {
        $this->seed();
        $token = $this->tokenFor('vendedor@edwin.com');

        $this->postJson('/api/clients', [
            'name' => 'María López',
            'phone' => '3008887766',
            'address' => 'Sincelejo',
        ], $this->bearer($token))
            ->assertCreated()
            ->assertJsonPath('data.name', 'María López');

        $this->assertDatabaseHas('clients', [
            'name' => 'María López',
            'phone' => '3008887766',
        ]);
    }

    public function test_admin_can_update_client(): void
    {
        $this->seed();
        $token = $this->tokenFor('admin@edwin.com');
        $client = Client::query()->first();

        $this->patchJson("/api/clients/{$client->id}", [
            'phone' => '3001119999',
        ], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.phone', '3001119999');
    }

    public function test_vendedor_cannot_update_client(): void
    {
        $this->seed();
        $token = $this->tokenFor('vendedor@edwin.com');
        $client = Client::query()->first();

        $this->patchJson("/api/clients/{$client->id}", [
            'phone' => '3001119999',
        ], $this->bearer($token))
            ->assertForbidden();
    }
}
