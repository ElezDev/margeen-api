<?php

namespace App\Services;

use App\Models\Product;
use App\Services\MeasurementUnitService;
use App\Support\SpreadsheetReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ProductImportService
{
    /** @var array<string, list<string>> */
    private const COLUMN_ALIASES = [
        'name' => ['nombre', 'name', 'producto'],
        'unit' => ['unidad', 'unit'],
        'cost_price' => ['precio_costo', 'costo', 'cost_price', 'precio_compra'],
        'sale_price' => ['precio_venta', 'venta', 'sale_price', 'precio'],
        'is_active' => ['activo', 'is_active', 'estado'],
    ];

    public function __construct(
        private readonly SpreadsheetReader $spreadsheetReader,
        private readonly MeasurementUnitService $measurementUnitService
    ) {}

    public function import(int $companyId, UploadedFile $file): array
    {
        $rows = $this->spreadsheetReader->rowsFromUpload($file, self::COLUMN_ALIASES);

        if ($rows === []) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [
                    ['row' => 1, 'message' => 'El archivo no tiene filas de datos o las columnas no coinciden con la plantilla.'],
                ],
            ];
        }

        if (! array_key_exists('name', $rows[0])) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [
                    ['row' => 1, 'message' => 'Falta la columna "nombre" en el archivo. Descarga la plantilla.'],
                ],
            ];
        }

        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        DB::transaction(function () use ($companyId, $rows, &$result) {
            foreach ($rows as $row) {
                $line = (int) $row['_row'];
                unset($row['_row']);

                if (($row['name'] ?? '') === '') {
                    $result['errors'][] = ['row' => $line, 'message' => 'El nombre es obligatorio.'];
                    $result['skipped']++;

                    continue;
                }

                $costPrice = $this->parseMoney($row['cost_price'] ?? '');
                $salePrice = $this->parseMoney($row['sale_price'] ?? '');

                if ($costPrice === null || $salePrice === null) {
                    $result['errors'][] = [
                        'row' => $line,
                        'message' => 'Precio costo y precio venta deben ser números válidos.',
                    ];
                    $result['skipped']++;

                    continue;
                }

                $unit = $this->measurementUnitService->resolveOrCreate(
                    $companyId,
                    ($row['unit'] ?? '') !== '' ? $row['unit'] : 'unidad'
                );

                $payload = [
                    'name' => $row['name'],
                    'unit_id' => $unit->id,
                    'unit' => $unit->name,
                    'cost_price' => $costPrice,
                    'sale_price' => $salePrice,
                    'is_active' => $this->parseBoolean($row['is_active'] ?? '1'),
                ];

                $existing = Product::query()
                    ->forCompany($companyId)
                    ->where('name', $payload['name'])
                    ->first();

                if ($existing) {
                    $existing->update($payload);
                    $result['updated']++;

                    continue;
                }

                Product::query()->create([
                    ...$payload,
                    'company_id' => $companyId,
                ]);
                $result['created']++;
            }
        });

        return $result;
    }

    public function templatePath(): string
    {
        return $this->spreadsheetReader->downloadXlsx(
            ['nombre', 'unidad', 'precio_costo', 'precio_venta', 'activo'],
            self::templateSampleRows(),
            'plantilla-productos.xlsx'
        );
    }

    /**
     * @return list<array{0: string, 1: string, 2: int|float, 3: int|float, 4: string}>
     */
    private static function templateSampleRows(): array
    {
        return [
            ['Arroz premium', 'Arroba', 18000, 24000, 'si'],
            ['Aceite vegetal', 'Galón', 12000, 15500, 'si'],
            ['Frijol rojo', 'Bulto', 80000, 95000, 'si'],
            ['Azúcar blanca', 'Bulto', 45000, 58000, 'si'],
            ['Sal refisal', 'Bulto', 12000, 15000, 'si'],
            ['Harina de trigo', 'Bulto', 35000, 42000, 'si'],
            ['Pasta spaghetti', 'Caja', 28000, 35000, 'si'],
            ['Atún en lata', 'Unidad', 6500, 8500, 'si'],
            ['Sardinas en lata', 'Unidad', 4500, 6000, 'si'],
            ['Leche entera', 'Bolsa', 3200, 4200, 'si'],
            ['Café molido', 'Libra', 8500, 11000, 'si'],
            ['Panela redonda', 'Unidad', 4500, 6000, 'si'],
            ['Avena en hojuelas', 'Bolsa', 5500, 7200, 'si'],
            ['Chocolate en polvo', 'Bolsa', 7800, 9800, 'si'],
            ['Galletas surtidas', 'Paquete', 4200, 5500, 'si'],
            ['Detergente líquido', 'Unidad', 9800, 12500, 'si'],
            ['Jabón de barra', 'Unidad', 2500, 3500, 'si'],
            ['Papel higiénico', 'Paquete', 11500, 14500, 'si'],
            ['Cepillo dental', 'Unidad', 3500, 5000, 'si'],
            ['Shampoo familiar', 'Unidad', 8900, 11500, 'si'],
        ];
    }

    private function parseMoney(string $value): ?float
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $value = str_replace(['$', ' '], '', $value);

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    private function parseBoolean(string $value): bool
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return true;
        }

        return in_array($value, ['1', 'true', 'si', 'sí', 'yes', 'activo', 'active'], true);
    }
}
