<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Services\PlatformRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private readonly PlatformRoleService $platformRoleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('platform.manage'), 403);

        return response()->json([
            'data' => RoleResource::collection($this->platformRoleService->listRoles()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('platform.manage'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:roles,name,NULL,id,guard_name,api'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name,guard_name,api'],
        ]);

        try {
            $role = $this->platformRoleService->createRole(
                $validated['name'],
                $validated['permissions'] ?? []
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Rol creado.',
            'data' => RoleResource::make($role),
        ], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()?->can('platform.manage'), 403);

        if ($role->guard_name !== 'api') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:roles,name,'.$role->id.',id,guard_name,api'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name,guard_name,api'],
        ]);

        try {
            $role = $this->platformRoleService->updateRole($role, $validated);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Rol actualizado.',
            'data' => RoleResource::make($role),
        ]);
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()?->can('platform.manage'), 403);

        if ($role->guard_name !== 'api') {
            abort(404);
        }

        try {
            $this->platformRoleService->deleteRole($role);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Rol eliminado.']);
    }
}
