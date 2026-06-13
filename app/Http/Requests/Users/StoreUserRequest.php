<?php

namespace App\Http\Requests\Users;

use App\Enums\Role as RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

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
            'role' => ['required', new Enum(RoleEnum::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
