<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class PlatformCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function superAdminToken(): string
    {
        $this->seed();

        return JWTAuth::fromUser(
            User::query()->where('email', 'superadmin@margeen.com')->firstOrFail()
        );
    }

    private function adminToken(): string
    {
        $this->seed();

        return JWTAuth::fromUser(
            User::query()->where('email', 'admin@demo.com')->firstOrFail()
        );
    }

    public function test_super_admin_can_list_tenant_companies(): void
    {
        $token = $this->superAdminToken();

        $response = $this->getJson('/api/platform/companies', $this->bearer($token))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'document', 'is_active', 'users_count'],
                ],
            ]);

        $documents = collect($response->json('data'))->pluck('document');
        $this->assertFalse($documents->contains('PLATFORM'));
    }

    public function test_regular_admin_cannot_access_platform_companies(): void
    {
        $token = $this->adminToken();

        $this->getJson('/api/platform/companies', $this->bearer($token))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_company_with_admin_user(): void
    {
        $token = $this->superAdminToken();

        $this->postJson('/api/platform/companies', [
            'name' => 'Distribuidora Norte',
            'document' => '900555444',
            'phone' => '3009998877',
            'default_margin_percent' => 20,
            'admin_name' => 'Pedro Admin',
            'admin_email' => 'pedro@norte.com',
            'admin_password' => 'password123',
        ], $this->bearer($token))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Distribuidora Norte')
            ->assertJsonPath('data.users_count', 1);

        $this->assertDatabaseHas('companies', [
            'name' => 'Distribuidora Norte',
            'document' => '900555444',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'pedro@norte.com',
        ]);
    }

    public function test_super_admin_can_update_company_and_upload_logo(): void
    {
        $token = $this->superAdminToken();
        $company = Company::query()->where('document', '900123456')->firstOrFail();

        $this->patchJson("/api/platform/companies/{$company->id}", [
            'name' => 'Margeen Actualizado',
            'notes' => 'Cliente premium',
            'is_active' => true,
        ], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.name', 'Margeen Actualizado')
            ->assertJsonPath('data.notes', 'Cliente premium');

        $file = UploadedFile::fake()->image('logo.png');

        $this->post("/api/platform/companies/{$company->id}/logo", [
            'logo' => $file,
        ], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.logo_path', "companies/{$company->id}/logo.png")
            ->assertJsonPath('data.logo_url', "/storage/companies/{$company->id}/logo.png");

        $company->refresh();
        Storage::disk('public')->assertExists($company->logo_path);

        $this->get("/api/platform/companies/{$company->id}/logo", $this->bearer($token))
            ->assertRedirect();
    }

    public function test_super_admin_cannot_modify_platform_company(): void
    {
        $token = $this->superAdminToken();
        $platformCompany = Company::query()->where('document', 'PLATFORM')->firstOrFail();

        $this->patchJson("/api/platform/companies/{$platformCompany->id}", [
            'name' => 'Hack',
        ], $this->bearer($token))
            ->assertForbidden();
    }

    public function test_legacy_logo_is_migrated_with_relative_url(): void
    {
        Storage::fake('local');

        $token = $this->superAdminToken();
        $company = Company::query()->where('document', '900123456')->firstOrFail();
        $path = "companies/{$company->id}/logo.png";

        Storage::disk('local')->put($path, 'legacy-logo');
        $company->update(['logo_path' => $path]);

        $this->getJson('/api/platform/companies', $this->bearer($token))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $company->id,
                'logo_url' => "/storage/{$path}",
            ]);

        Storage::disk('public')->assertExists($path);
        Storage::disk('local')->assertMissing($path);
    }
}
