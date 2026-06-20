<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    private function superAdminToken(): string
    {
        $this->seed();

        return JWTAuth::fromUser(
            User::query()->where('email', 'superadmin@margeen.com')->firstOrFail()
        );
    }

    public function test_super_admin_can_manage_tenant_with_company_header(): void
    {
        $token = $this->superAdminToken();
        $company = Company::query()->where('document', '900123456')->firstOrFail();

        $this->withHeaders([
            ...$this->bearer($token),
            'X-Company-Id' => (string) $company->id,
        ])->postJson('/api/clients', [
            'name' => 'Cliente Super Admin',
            'phone' => '3000000011',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Cliente Super Admin');

        $this->withHeaders([
            ...$this->bearer($token),
            'X-Company-Id' => (string) $company->id,
        ])->getJson('/api/clients')
            ->assertOk()
            ->assertJsonFragment(['name' => 'María López'])
            ->assertJsonFragment(['name' => 'Cliente Super Admin']);
    }

    public function test_super_admin_can_create_invoice_for_tenant(): void
    {
        $token = $this->superAdminToken();
        $company = Company::query()->where('document', '900123456')->firstOrFail();
        $client = Client::query()->where('company_id', $company->id)->firstOrFail();
        $product = Product::query()->where('company_id', $company->id)->firstOrFail();

        $this->withHeaders([
            ...$this->bearer($token),
            'X-Company-Id' => (string) $company->id,
        ])->postJson('/api/invoices', [
            'client_id' => $client->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.status', 'issued');
    }
}
