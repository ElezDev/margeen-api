<?php

namespace App\Services;

use App\Models\MeasurementUnit;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MeasurementUnitService
{
    /** @var list<string> */
    public const DEFAULT_NAMES = [
        'Unidad',
        'Arroba',
        'Bulto',
        'Galón',
        'Libra',
        'Kilogramo',
        'Caja',
        'Paquete',
        'Bolsa',
        'Litro',
        'Metro',
        'Docena',
    ];

    public function listForCompany(int $companyId, bool $activeOnly = true): Collection
    {
        $query = MeasurementUnit::query()
            ->forCompany($companyId)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    public function seedDefaultsForCompany(int $companyId): void
    {
        foreach (self::DEFAULT_NAMES as $index => $name) {
            MeasurementUnit::query()->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $name,
                ],
                [
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }

    public function resolveOrCreate(int $companyId, string $name): MeasurementUnit
    {
        $this->seedDefaultsForCompany($companyId);

        $normalized = self::normalize($name);

        $existing = MeasurementUnit::query()
            ->forCompany($companyId)
            ->get()
            ->first(fn (MeasurementUnit $unit) => self::normalize($unit->name) === $normalized);

        if ($existing) {
            return $existing;
        }

        $displayName = trim($name) !== '' ? Str::title(trim($name)) : 'Unidad';

        return MeasurementUnit::query()->create([
            'company_id' => $companyId,
            'name' => $displayName,
            'sort_order' => 100,
            'is_active' => true,
        ]);
    }

    public static function normalize(string $value): string
    {
        return Str::ascii(Str::lower(trim($value)));
    }
}
