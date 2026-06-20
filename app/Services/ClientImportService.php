<?php

namespace App\Services;

use App\Models\Client;
use App\Support\SpreadsheetReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ClientImportService
{
    /** @var array<string, list<string>> */
    private const COLUMN_ALIASES = [
        'name' => ['nombre', 'name', 'cliente'],
        'document' => ['documento', 'document', 'nit', 'cc', 'cedula'],
        'phone' => ['telefono', 'phone', 'celular', 'movil'],
        'address' => ['direccion', 'address'],
        'notes' => ['notas', 'notes', 'observaciones'],
    ];

    public function __construct(
        private readonly SpreadsheetReader $spreadsheetReader
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

                $payload = [
                    'name' => $row['name'],
                    'document' => $this->normalizeDocument($row['document'] ?? ''),
                    'phone' => $row['phone'] ?: null,
                    'address' => $row['address'] ?: null,
                    'notes' => $row['notes'] ?: null,
                ];

                $existing = null;

                if ($payload['document']) {
                    $existing = Client::query()
                        ->forCompany($companyId)
                        ->where('document', $payload['document'])
                        ->first();
                }

                if ($existing) {
                    $existing->update($payload);
                    $result['updated']++;

                    continue;
                }

                Client::query()->create([
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
            ['nombre', 'documento', 'telefono', 'direccion', 'notas'],
            [
                ['María López', '1088123456', '3005556677', 'Sincelejo', 'Cliente frecuente'],
                ['Pedro Gómez', '1099887766', '3001112233', 'Corozal', ''],
            ],
            'plantilla-clientes.xlsx'
        );
    }

    private function normalizeDocument(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (string) (int) round((float) $value);
        }

        return $value;
    }
}
