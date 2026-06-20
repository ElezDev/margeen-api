<?php

namespace App\Http\Requests\Products;

use App\Models\MeasurementUnit;
use App\Support\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'unit_id' => [
                'required_without:unit',
                'nullable',
                'integer',
                Rule::exists(MeasurementUnit::class, 'id')
                    ->where('company_id', Tenant::companyId($this)),
            ],
            'unit' => ['required_without:unit_id', 'nullable', 'string', 'max:50'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
