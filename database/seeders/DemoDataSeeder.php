<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Services\MeasurementUnitService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('document', '900123456')->first();

        if (! $company) {
            return;
        }

        app(MeasurementUnitService::class)->seedDefaultsForCompany($company->id);

        $arroba = MeasurementUnit::query()
            ->where('company_id', $company->id)
            ->where('name', 'Arroba')
            ->firstOrFail();

        $galon = MeasurementUnit::query()
            ->where('company_id', $company->id)
            ->where('name', 'Galón')
            ->firstOrFail();

        Client::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'document' => '1088123456',
            ],
            [
                'name' => 'María López',
                'phone' => '3005556677',
                'address' => 'Corozal, Sucre',
                'notes' => 'Cliente frecuente',
            ]
        );

        Product::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Arroz premium',
            ],
            [
                'unit_id' => $arroba->id,
                'unit' => $arroba->name,
                'cost_price' => 18000,
                'sale_price' => 24000,
                'is_active' => true,
            ]
        );

        Product::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Aceite vegetal',
            ],
            [
                'unit_id' => $galon->id,
                'unit' => $galon->name,
                'cost_price' => 12000,
                'sale_price' => 15500,
                'is_active' => true,
            ]
        );
    }
}
