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
            'unit' => 'Bulto',
            'cost_price' => 80000,
            'sale_price' => 95000,
        ], $this->bearer($token))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Frijol rojo')
            ->assertJsonPath('data.unit', 'Bulto');

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

    public function test_products_are_paginated(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@demo.com')->firstOrFail();
        $unitId = \App\Models\MeasurementUnit::query()
            ->where('company_id', $admin->company_id)
            ->value('id');

        for ($i = 1; $i <= 16; $i++) {
            Product::query()->create([
                'company_id' => $admin->company_id,
                'name' => "Producto paginado {$i}",
                'unit_id' => $unitId,
                'unit' => 'Unidad',
                'cost_price' => 1000,
                'sale_price' => 1500,
            ]);
        }

        $token = $this->tokenFor('admin@demo.com');

        $this->getJson('/api/products?page=1&per_page=15', $this->bearer($token))
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson('/api/products?page=2&per_page=15', $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2);
    }
}
