<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->create([
            'name' => 'Distribuciones Edwin',
            'document' => '900123456',
            'phone' => '3001234567',
            'address' => 'Calle 10 #5-20, Sincelejo',
            'default_margin_percent' => 25,
            'invoice_prefix' => 'FAC',
            'next_invoice_number' => 1,
        ]);

        User::query()->create([
            'company_id' => $company->id,
            'name' => 'Edwin Admin',
            'email' => 'admin@edwin.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        User::query()->create([
            'company_id' => $company->id,
            'name' => 'Carlos Vendedor',
            'email' => 'vendedor@edwin.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Vendedor,
            'is_active' => true,
        ]);
    }
}
