<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class MeasurementUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_measurement_units(): void
    {
        $this->seed();

        $token = JWTAuth::fromUser(
            User::query()->where('email', 'admin@demo.com')->firstOrFail()
        );

        $this->getJson('/api/measurement-units', $this->bearer($token))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Unidad'])
            ->assertJsonFragment(['name' => 'Arroba'])
            ->assertJsonFragment(['name' => 'Galón']);
    }
}
