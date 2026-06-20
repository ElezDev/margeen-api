<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Services\MeasurementUnitService;
use Illuminate\Database\Seeder;

class MeasurementUnitSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(MeasurementUnitService::class);

        Company::query()->each(
            fn (Company $company) => $service->seedDefaultsForCompany($company->id)
        );
    }
}
