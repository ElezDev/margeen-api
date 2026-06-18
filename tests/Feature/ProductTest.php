<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(string $email): string
    {
        return JWTAuth::fromUser(
            User::query()->where('email', $email)->firstOrFail()
        );
    }

    public function test_vendedor_can_list_and_search_products(): void
    {
        $this->seed();
        $token = $this->tokenFor('vendedor@demo.com');

        $this->getJson('/api/products?q=arroz', $this->bearer($token))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Arroz premium');

        $this->getJson('/api/products?active_only=1', $this->bearer($token))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_create_and_update_product(): void
    {
        $this->seed();
        $token = $this->tokenFor('admin@demo.com');

        $this->postJson('/api/products', [
            'name' => 'Frijol rojo',
            'unit' => 'bulto',
            'cost_price' => 80000,
            'sale_price' => 95000,
        ], $this->bearer($token))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Frijol rojo');

        $product = Product::query()->where('name', 'Frijol rojo')->first();

        $this->patchJson("/api/products/{$product->id}", [
            'sale_price' => 98000,
        ], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.sale_price', '98000.00');
    }

    public function test_vendedor_cannot_create_product(): void
    {
        $this->seed();
        $token = $this->tokenFor('vendedor@demo.com');

        $this->postJson('/api/products', [
            'name' => 'Test',
            'cost_price' => 1000,
            'sale_price' => 1500,
        ], $this->bearer($token))
            ->assertForbidden();
    }
}
