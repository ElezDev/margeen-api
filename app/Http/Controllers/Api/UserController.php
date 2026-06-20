<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->forCompany(Tenant::companyId($request))
            ->with('roles.permissions')
            ->orderBy('name')
            ->get();

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = $data['role'];
        unset($data['role']);

        $user = User::query()->create([
            ...$data,
            'company_id' => Tenant::companyId($request),
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->assignRole($role);
        $user->load('roles.permissions');

        return response()->json([
            'message' => 'Usuario creado.',
            'data' => UserResource::make($user),
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->ensureSameCompany($request, $user);

        $user->load('roles.permissions');

        return response()->json([
            'data' => UserResource::make($user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->ensureSameCompany($request, $user);

        $data = $request->validated();

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
            unset($data['role']);
        }

        $user->update($data);
        $user->load('roles.permissions');

        return response()->json([
            'message' => 'Usuario actualizado.',
            'data' => UserResource::make($user),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->ensureSameCompany($request, $user);

        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'No puedes desactivar tu propio usuario.',
            ], 422);
        }

        $user->update(['is_active' => false]);

        return response()->json([
            'message' => 'Usuario desactivado.',
        ]);
    }

    private function ensureSameCompany(Request $request, User $user): void
    {
        if (! Tenant::belongsToTenant($user, $request)) {
            abort(404);
        }
    }
}
