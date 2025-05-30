<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'User must be authenticated to access this resource.'
            ], 401);
        }

        $user = auth()->user();

        if (!$user->can($permission)) {
            return response()->json([
                'message' => 'Insufficient Permissions',
                'error' => "You do not have permission to perform this action. Required permission: {$permission}",
                'required_permission' => $permission,
                'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
            ], 403);
        }

        return $next($request);
    }
} 