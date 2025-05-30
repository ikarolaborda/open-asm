<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check()) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'User must be authenticated to access this resource.',
            ], 401);
        }

        $user = auth()->user();

        if (! $user->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'Insufficient Role',
                'error' => 'You do not have the required role to access this resource. Required roles: ' . implode(', ', $roles),
                'required_roles' => $roles,
                'user_roles' => $user->getRoleNames()->toArray(),
            ], 403);
        }

        return $next($request);
    }
}
