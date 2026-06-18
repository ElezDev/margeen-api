<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->create([
            'name' => 'Distribuciones Margeen',
            'document' => '900123456',
            'phone' => '3001234567',
            'address' => 'Calle 10 #5-20, Sincelejo',
            'default_margin_percent' => 25,
            'invoice_prefix' => 'FAC',
            'next_invoice_number' => 1,
        ]);

        $admin = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Ana Rodríguez',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password'),
            'document' => '1234567890',
            'phone' => '3001112233',
            'address' => 'Barrio El Centro, Sincelejo',
            'notes' => 'Dueño del negocio',
            'is_active' => true,
        ]);
        $admin->assignRole(RoleEnum::Admin->value);

        $vendedor = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Carlos Vendedor',
            'email' => 'vendedor@demo.com',
            'password' => Hash::make('password'),
            'document' => '9876543210',
            'phone' => '3004445566',
            'address' => 'Corozal, Sucre',
            'notes' => 'Ruta zona norte',
            'is_active' => true,
        ]);
        $vendedor->assignRole(RoleEnum::Vendedor->value);
    }
}
