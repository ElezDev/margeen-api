<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        $allowedRoles = array_map(
            fn (string $role) => UserRole::from($role),
            $roles
        );

        if (! in_array($user->role, $allowedRoles, true)) {
            abort(403, 'No tienes permisos para esta acción.');
        }

        return $next($request);
    }
}
