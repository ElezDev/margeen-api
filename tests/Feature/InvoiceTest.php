<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class InvoiceTest extends TestCase
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

    public function test_vendedor_can_create_invoice_with_profit_and_pdf(): void
    {
        $this->seed();

        $client = Client::query()->first();
        $product = Product::query()->where('name', 'Arroz premium')->first();
        $token = $this->tokenFor('vendedor@demo.com');

        $response = $this->postJson('/api/invoices', [
            'client_id' => $client->id,
            'notes' => 'Entrega mañana',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 24000,
                    'unit_cost' => 18000,
                ],
            ],
        ], $this->bearer($token));

        $response->assertCreated()
            ->assertJsonPath('data.number', 'FAC-0001')
            ->assertJsonPath('data.status', 'issued')
            ->assertJsonPath('data.subtotal', '240000.00')
            ->assertJsonPath('data.total', '240000.00')
            ->assertJsonPath('data.total_cost', '180000.00')
            ->assertJsonPath('data.total_profit', '60000.00')
            ->assertJsonPath('data.profit_margin_percent', 25)
            ->assertJsonPath('data.items.0.description', 'Arroz premium')
            ->assertJsonStructure(['data' => ['pdf_path', 'pdf_url']]);

        $invoice = Invoice::query()->first();
        Storage::disk('local')->assertExists($invoice->pdf_path);

        $this->get("/api/invoices/{$invoice->id}/pdf", $this->bearer($token))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_vendedor_only_sees_own_invoices(): void
    {
        $this->seed();

        $client = Client::query()->first();
        $product = Product::query()->first();
        $vendedor = User::query()->where('email', 'vendedor@demo.com')->firstOrFail();
        $token = $this->tokenFor('vendedor@demo.com');

        $this->postJson('/api/invoices', [
            'client_id' => $client->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ], $this->bearer($token))->assertCreated();

        $this->postJson('/api/invoices', [
            'client_id' => $client->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ], $this->bearer($token))->assertCreated();

        $this->getJson('/api/invoices', $this->bearer($token))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        Invoice::query()->create([
            'company_id' => $vendedor->company_id,
            'client_id' => $client->id,
            'user_id' => User::query()->where('email', 'admin@demo.com')->value('id'),
            'number' => 'FAC-0099',
            'status' => 'issued',
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'total_cost' => 800,
            'total_profit' => 200,
            'issued_at' => now(),
        ]);

        $this->getJson('/api/invoices', $this->bearer($token))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_sees_all_invoices(): void
    {
        $this->seed();

        $client = Client::query()->first();
        $product = Product::query()->first();
        $adminToken = $this->tokenFor('admin@demo.com');
        $vendedorToken = $this->tokenFor('vendedor@demo.com');

        $this->postJson('/api/invoices', [
            'client_id' => $client->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ], $this->bearer($vendedorToken))->assertCreated();

        $this->getJson('/api/invoices', $this->bearer($adminToken))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_cancel_invoice(): void
    {
        $this->seed();

        $client = Client::query()->first();
        $product = Product::query()->first();
        $token = $this->tokenFor('admin@demo.com');

        $invoiceId = $this->postJson('/api/invoices', [
            'client_id' => $client->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ], $this->bearer($token))
            ->json('data.id');

        $this->patchJson("/api/invoices/{$invoiceId}/cancel", [], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_vendedor_cannot_cancel_invoice(): void
    {
        $this->seed();

        $client = Client::query()->first();
        $product = Product::query()->first();
        $token = $this->tokenFor('vendedor@demo.com');

        $invoiceId = $this->postJson('/api/invoices', [
            'client_id' => $client->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ], $this->bearer($token))
            ->json('data.id');

        $this->patchJson("/api/invoices/{$invoiceId}/cancel", [], $this->bearer($token))
            ->assertForbidden();
    }
}
