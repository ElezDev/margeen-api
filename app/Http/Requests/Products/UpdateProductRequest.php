<?php

namespace App\Http\Requests\Products;

use App\Models\MeasurementUnit;
use App\Support\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'unit_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists(MeasurementUnit::class, 'id')
                    ->where('company_id', Tenant::companyId($this)),
            ],
            'unit' => ['sometimes', 'nullable', 'string', 'max:50'],
            'cost_price' => ['sometimes', 'numeric', 'min:0'],
            'sale_price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
