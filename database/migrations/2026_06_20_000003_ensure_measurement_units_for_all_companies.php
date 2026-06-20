<?php

use App\Models\Company;
use App\Services\MeasurementUnitService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $service = app(MeasurementUnitService::class);

        Company::query()->each(
            fn (Company $company) => $service->seedDefaultsForCompany($company->id)
        );
    }

    public function down(): void
    {
        //
    }
};
