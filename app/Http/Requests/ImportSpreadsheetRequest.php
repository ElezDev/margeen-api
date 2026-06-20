<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportSpreadsheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:5120',
                'mimes:xlsx,xls,csv,txt',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
        ];
    }
}
