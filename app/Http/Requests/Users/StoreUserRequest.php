<?php

namespace App\Http\Requests\Users;

use App\Support\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = Tenant::companyId($this);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where('company_id', $companyId),
            ],
            'password' => ['required', 'string', 'min:8'],
            'document' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'document')->where('company_id', $companyId),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'avatar_path' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'api'),
                Rule::notIn(['super_admin']),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
