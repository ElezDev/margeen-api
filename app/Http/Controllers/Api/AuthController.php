<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->with('company')
            ->where('email', $request->validated('email'))
            ->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Usuario inactivo.',
            ], 403);
        }

        $tokens = $this->authService->login($user);

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'data' => [
                ...$tokens,
                'user' => UserResource::make($user),
            ],
        ]);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $tokens = $this->authService->refresh($request->validated('refresh_token'));

        return response()->json([
            'message' => 'Token renovado.',
            'data' => $tokens,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout(
            $request->user(),
            $request->input('refresh_token')
        );

        return response()->json([
            'message' => 'Sesión cerrada.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('company');

        return response()->json([
            'data' => UserResource::make($user),
        ]);
    }
}
