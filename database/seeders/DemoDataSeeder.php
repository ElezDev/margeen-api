<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->first();

        if (! $company) {
            return;
        }

        Client::query()->create([
            'company_id' => $company->id,
            'name' => 'Edwin Pérez',
            'document' => '1088123456',
            'phone' => '3005556677',
            'address' => 'Corozal, Sucre',
            'notes' => 'Cliente frecuente',
        ]);

        Product::query()->create([
            'company_id' => $company->id,
            'name' => 'Arroz premium',
            'unit' => 'arroba',
            'cost_price' => 18000,
            'sale_price' => 24000,
            'is_active' => true,
        ]);

        Product::query()->create([
            'company_id' => $company->id,
            'name' => 'Aceite vegetal',
            'unit' => 'galón',
            'cost_price' => 12000,
            'sale_price' => 15500,
            'is_active' => true,
        ]);
    }
}
