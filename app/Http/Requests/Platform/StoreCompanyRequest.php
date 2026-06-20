<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:30', Rule::unique('companies', 'document')],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'default_margin_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'invoice_prefix' => ['nullable', 'string', 'max:10'],
            'admin_name' => ['nullable', 'string', 'max:255', 'required_with:admin_email,admin_password'],
            'admin_email' => ['nullable', 'email', 'max:255', 'required_with:admin_name,admin_password'],
            'admin_password' => ['nullable', 'string', 'min:8', 'required_with:admin_name,admin_email'],
        ];
    }
}
