<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Services\PlatformRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PlatformRoleService $platformRoleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('platform.manage'), 403);

        return response()->json([
            'data' => PermissionResource::collection($this->platformRoleService->listPermissions()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('platform.manage'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9._-]*$/', 'unique:permissions,name,NULL,id,guard_name,api'],
        ]);

        $permission = $this->platformRoleService->createPermission($validated['name']);

        return response()->json([
            'message' => 'Permiso creado.',
            'data' => PermissionResource::make($permission),
        ], 201);
    }

    public function destroy(Request $request, Permission $permission): JsonResponse
    {
        abort_unless($request->user()?->can('platform.manage'), 403);

        if ($permission->guard_name !== 'api') {
            abort(404);
        }

        try {
            $this->platformRoleService->deletePermission($permission);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Permiso eliminado.']);
    }
}
