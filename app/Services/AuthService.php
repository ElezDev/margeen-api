<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    private const REFRESH_TOKEN_TTL_DAYS = 30;

    public function login(User $user): array
    {
        $accessToken = JWTAuth::fromUser($user);
        $refreshToken = $this->createRefreshToken($user);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    public function refresh(string $refreshToken): array
    {
        $storedToken = RefreshToken::query()
            ->where('token', hash('sha256', $refreshToken))
            ->first();

        if (! $storedToken || $storedToken->isExpired()) {
            abort(401, 'Refresh token inválido o expirado.');
        }

        $user = $storedToken->user;

        if (! $user->is_active) {
            abort(403, 'Usuario inactivo.');
        }

        $storedToken->delete();

        return $this->login($user);
    }

    public function logout(User $user, ?string $refreshToken = null): void
    {
        if ($refreshToken) {
            RefreshToken::query()
                ->where('user_id', $user->id)
                ->where('token', hash('sha256', $refreshToken))
                ->delete();
        }

        try {
            JWTAuth::parseToken()->invalidate();
        } catch (\Throwable) {
            // Token ya inválido o ausente; logout idempotente.
        }
    }

    private function createRefreshToken(User $user): string
    {
        $plainToken = Str::random(64);

        RefreshToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(self::REFRESH_TOKEN_TTL_DAYS),
        ]);

        return $plainToken;
    }
}
