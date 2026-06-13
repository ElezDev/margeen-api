<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'document' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'document')
                    ->where('company_id', $user->company_id)
                    ->ignore($user->id),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'avatar_path' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
