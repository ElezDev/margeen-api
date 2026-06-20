<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $this->seed();

        return JWTAuth::fromUser(
            User::query()->where('email', 'admin@demo.com')->firstOrFail()
        );
    }

    public function test_admin_can_import_clients_from_csv(): void
    {
        $token = $this->adminToken();
        $csv = implode("\n", [
            'nombre,documento,telefono,direccion,notas',
            'Cliente Importado,1099112233,3009998877,Sincelejo,Desde Excel',
            'Cliente Actualizable,5551234567,3001110000,Corozal,Pendiente',
            'Cliente Actualizable,5551234567,3002220000,Corozal,Actualizado',
        ]);

        $file = UploadedFile::fake()->createWithContent('clientes.csv', $csv);

        $this->post('/api/clients/import', ['file' => $file], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.created', 2)
            ->assertJsonPath('data.updated', 1);

        $this->assertDatabaseHas('clients', [
            'name' => 'Cliente Importado',
            'document' => '1099112233',
        ]);

        $this->assertDatabaseHas('clients', [
            'document' => '5551234567',
            'phone' => '3002220000',
            'notes' => 'Actualizado',
        ]);
    }

    public function test_admin_can_import_products_from_csv(): void
    {
        $token = $this->adminToken();
        $csv = implode("\n", [
            'nombre,unidad,precio_costo,precio_venta,activo',
            'Frijol rojo,bulto,80000,95000,si',
            'Arroz premium,arroba,19000,25000,si',
        ]);

        $file = UploadedFile::fake()->createWithContent('productos.csv', $csv);

        $this->post('/api/products/import', ['file' => $file], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.updated', 1);

        $this->assertDatabaseHas('products', [
            'name' => 'Frijol rojo',
            'sale_price' => 95000,
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Arroz premium',
            'sale_price' => 25000,
        ]);
    }

    public function test_admin_can_download_client_import_template(): void
    {
        $token = $this->adminToken();

        $this->get('/api/clients/import/template', $this->bearer($token))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_admin_can_download_product_import_template(): void
    {
        $token = $this->adminToken();

        $this->get('/api/products/import/template', $this->bearer($token))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_vendedor_cannot_import_products(): void
    {
        $this->seed();
        $token = JWTAuth::fromUser(
            User::query()->where('email', 'vendedor@demo.com')->firstOrFail()
        );

        $file = UploadedFile::fake()->createWithContent('productos.csv', "nombre,precio_costo,precio_venta\nX,1,2\n");

        $this->post('/api/products/import', ['file' => $file], $this->bearer($token))
            ->assertForbidden();
    }
}
