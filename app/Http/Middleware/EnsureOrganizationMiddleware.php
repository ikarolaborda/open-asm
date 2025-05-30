<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'User must be authenticated to access this resource.',
            ], 401);
        }

        $user = auth()->user();

        // Super admins can access any organization
        if ($user->isSuperAdmin()) {
            // Set a default organization context if available
            $request->attributes->set('organization', $user->organization);
            $request->attributes->set('organization_id', $user->organization_id);
            $request->attributes->set('is_super_admin', true);

            return $next($request);
        }

        // Regular users must have an organization assigned
        if (! $user->organization_id) {
            return response()->json([
                'message' => 'Organization Required',
                'error' => 'User must be assigned to an organization to access this resource.',
            ], 403);
        }

        if (! $user->organization || ! $user->organization->isActive()) {
            return response()->json([
                'message' => 'Organization Inactive',
                'error' => 'User\'s organization is not active or does not exist.',
            ], 403);
        }

        // Check if user is active
        if (! $user->isActive()) {
            return response()->json([
                'message' => 'User Inactive',
                'error' => 'User account is not active.',
            ], 403);
        }

        // Set the current organization in the request for use in controllers
        $request->attributes->set('organization', $user->organization);
        $request->attributes->set('organization_id', $user->organization_id);
        $request->attributes->set('is_super_admin', false);

        return $next($request);
    }
}
