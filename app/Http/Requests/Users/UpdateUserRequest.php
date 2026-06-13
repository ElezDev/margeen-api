<?php

namespace App\Http\Requests\Users;

use App\Enums\Role as RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;
        $userId = $this->route('user')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where('company_id', $companyId)
                    ->ignore($userId),
            ],
            'password' => ['sometimes', 'string', 'min:8'],
            'document' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'document')
                    ->where('company_id', $companyId)
                    ->ignore($userId),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'avatar_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'role' => ['sometimes', new Enum(RoleEnum::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
