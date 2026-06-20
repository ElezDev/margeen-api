<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SpreadsheetReader
{
    public const MAX_ROWS = 2000;

    /** @param  array<string, list<string>>  $columnAliases */
    public function rowsFromUpload(UploadedFile $file, array $columnAliases): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheetRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (count($sheetRows) < 2) {
            return [];
        }

        $headerRow = array_shift($sheetRows);
        $headerMap = $this->mapHeaders($headerRow, $columnAliases);
        $rows = [];

        foreach ($sheetRows as $index => $row) {
            $lineNumber = $index + 2;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                break;
            }

            $mapped = [];
            foreach ($headerMap as $columnIndex => $field) {
                $mapped[$field] = isset($row[$columnIndex])
                    ? trim((string) $row[$columnIndex])
                    : '';
            }

            $mapped['_row'] = $lineNumber;
            $rows[] = $mapped;
        }

        return $rows;
    }

    public function downloadXlsx(array $headers, array $sampleRows, string $filename): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');

        if ($sampleRows !== []) {
            $sheet->fromArray($sampleRows, null, 'A2');
        }

        $path = storage_path('app/temp/'.$filename);
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    /** @param  array<string, list<string>>  $columnAliases */
    private function mapHeaders(array $headerRow, array $columnAliases): array
    {
        $normalizedAliases = [];

        foreach ($columnAliases as $field => $aliases) {
            foreach ($aliases as $alias) {
                $normalizedAliases[$this->normalizeHeader($alias)] = $field;
            }
        }

        $map = [];

        foreach ($headerRow as $index => $header) {
            $normalized = $this->normalizeHeader((string) $header);

            if ($normalized === '' || ! isset($normalizedAliases[$normalized])) {
                continue;
            }

            $map[$index] = $normalizedAliases[$normalized];
        }

        return $map;
    }

    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $header
        );
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? $header;

        return trim($header, '_');
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
