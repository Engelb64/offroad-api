<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  string  ...$roles  Role values (user, workshop_owner, admin)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'No autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $allowed = collect($roles)
            ->map(fn (string $role) => UserRole::tryFrom($role))
            ->filter()
            ->all();

        $userRole = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);

        if (! $userRole || ! in_array($userRole, $allowed, true)) {
            return response()->json(['message' => 'No autorizado para esta accion.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
