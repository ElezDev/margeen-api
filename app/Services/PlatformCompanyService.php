<?php

namespace App\Services;

use App\Enums\Role as RoleEnum;
use App\Models\Company;
use App\Services\MeasurementUnitService;
use App\Models\User;
use App\Support\CompanyLogoStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PlatformCompanyService
{
    public function __construct(
        private readonly MeasurementUnitService $measurementUnitService
    ) {}

    public function list(?string $search = null)
    {
        $query = Company::query()
            ->where('document', '!=', 'PLATFORM')
            ->withCount(['users', 'clients', 'products', 'invoices'])
            ->latest('id');

        if ($search) {
            $term = '%'.$search.'%';
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('document', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        return $query->get();
    }

    public function create(array $data): Company
    {
        return DB::transaction(function () use ($data) {
            $adminData = [
                'name' => $data['admin_name'] ?? null,
                'email' => $data['admin_email'] ?? null,
                'password' => $data['admin_password'] ?? null,
            ];

            unset($data['admin_name'], $data['admin_email'], $data['admin_password']);

            $company = Company::query()->create([
                ...$data,
                'default_margin_percent' => $data['default_margin_percent'] ?? 0,
                'invoice_prefix' => $data['invoice_prefix'] ?? 'FAC',
                'next_invoice_number' => 1,
                'is_active' => $data['is_active'] ?? true,
            ]);

            if ($adminData['name'] && $adminData['email'] && $adminData['password']) {
                $admin = User::query()->create([
                    'company_id' => $company->id,
                    'name' => $adminData['name'],
                    'email' => $adminData['email'],
                    'password' => $adminData['password'],
                    'is_active' => true,
                ]);

                $admin->assignRole(RoleEnum::Admin->value);
            }

            $this->measurementUnitService->seedDefaultsForCompany($company->id);

            return $company->loadCount(['users', 'clients', 'products', 'invoices']);
        });
    }

    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->fresh()->loadCount(['users', 'clients', 'products', 'invoices']);
    }

    public function storeLogo(Company $company, UploadedFile $file): Company
    {
        $path = CompanyLogoStorage::store($company, $file);

        $company->update(['logo_path' => $path]);

        return $company->fresh()->loadCount(['users', 'clients', 'products', 'invoices']);
    }

    public function deleteLogo(Company $company): Company
    {
        CompanyLogoStorage::delete($company);
        $company->update(['logo_path' => null]);

        return $company->fresh()->loadCount(['users', 'clients', 'products', 'invoices']);
    }

    public function logoContents(Company $company): ?string
    {
        return CompanyLogoStorage::contents($company);
    }

    public function logoMimeType(Company $company): string
    {
        return CompanyLogoStorage::mimeType($company);
    }

    public function logoUrl(Company $company): ?string
    {
        return CompanyLogoStorage::url($company);
    }

    public function ensureTenantCompany(Company $company): void
    {
        if ($company->document === 'PLATFORM') {
            abort(403, 'No puedes modificar la empresa de plataforma.');
        }
    }

    public function migrateLegacyLogo(Company $company): void
    {
        CompanyLogoStorage::ensureOnPublicDisk($company);
    }
}
