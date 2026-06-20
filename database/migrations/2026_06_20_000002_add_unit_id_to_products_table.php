<?php

use App\Models\Company;
use App\Models\Product;
use App\Services\MeasurementUnitService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'unit_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('unit_id')
                    ->nullable()
                    ->after('name')
                    ->constrained('measurement_units')
                    ->restrictOnDelete();
            });
        }

        $service = app(MeasurementUnitService::class);

        Company::query()->each(function (Company $company) use ($service) {
            $service->seedDefaultsForCompany($company->id);

            Product::query()
                ->where('company_id', $company->id)
                ->whereNull('unit_id')
                ->each(function (Product $product) use ($service) {
                    $unit = $service->resolveOrCreate($product->company_id, $product->unit ?: 'unidad');
                    $product->update([
                        'unit_id' => $unit->id,
                        'unit' => $unit->name,
                    ]);
                });
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable(false)->change();
            $table->foreign('unit_id')
                ->references('id')
                ->on('measurement_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });
    }
};
