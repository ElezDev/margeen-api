<?php

namespace App\Http\Resources;

use App\Enums\Role as RoleEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'is_system' => in_array($this->name, [
                RoleEnum::SuperAdmin->value,
                RoleEnum::Admin->value,
                RoleEnum::Vendedor->value,
            ], true),
            'permissions' => $this->whenLoaded(
                'permissions',
                fn () => $this->permissions->pluck('name')->values()
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
