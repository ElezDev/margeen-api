<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function tokenFor(string $email): string
    {
        return JWTAuth::fromUser(
            User::query()->where('email', $email)->firstOrFail()
        );
    }

    public function test_vendedor_sees_own_dashboard_summary(): void
    {
        $this->seed();

        $client = Client::query()->first();
        $product = Product::query()->first();
        $token = $this->tokenFor('vendedor@edwin.com');

        $this->postJson('/api/invoices', [
            'client_id' => $client->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 24000, 'unit_cost' => 18000],
            ],
        ], $this->bearer($token))->assertCreated();

        $this->getJson('/api/reports/dashboard', $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.summary.invoice_count', 1)
            ->assertJsonPath('data.summary.total_sales', '240000.00')
            ->assertJsonPath('data.summary.total_profit', '60000.00')
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'summary',
                    'top_clients',
                    'top_products',
                    'recent_invoices',
                ],
            ]);
    }

    public function test_admin_sees_all_invoices_in_dashboard(): void
    {
        $this->seed();

        $client = Client::query()->first();
        $product = Product::query()->first();
        $adminToken = $this->tokenFor('admin@edwin.com');
        $vendedorToken = $this->tokenFor('vendedor@edwin.com');

        $this->postJson('/api/invoices', [
            'client_id' => $client->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ], $this->bearer($vendedorToken))->assertCreated();

        $this->postJson('/api/invoices', [
            'client_id' => $client->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ], $this->bearer($adminToken))->assertCreated();

        $this->getJson('/api/reports/dashboard', $this->bearer($adminToken))
            ->assertOk()
            ->assertJsonPath('data.summary.invoice_count', 2);
    }

    public function test_vendedor_dashboard_excludes_other_users_invoices(): void
    {
        $this->seed();

        $client = Client::query()->first();
        $product = Product::query()->first();
        $vendedor = User::query()->where('email', 'vendedor@edwin.com')->firstOrFail();
        $vendedorToken = $this->tokenFor('vendedor@edwin.com');

        $this->postJson('/api/invoices', [
            'client_id' => $client->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ], $this->bearer($vendedorToken))->assertCreated();

        Invoice::query()->create([
            'company_id' => $vendedor->company_id,
            'client_id' => $client->id,
            'user_id' => User::query()->where('email', 'admin@edwin.com')->value('id'),
            'number' => 'FAC-0099',
            'status' => InvoiceStatus::Issued,
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'total_cost' => 800,
            'total_profit' => 200,
            'issued_at' => now(),
        ]);

        $this->getJson('/api/reports/dashboard', $this->bearer($vendedorToken))
            ->assertOk()
            ->assertJsonPath('data.summary.invoice_count', 1);
    }
}
