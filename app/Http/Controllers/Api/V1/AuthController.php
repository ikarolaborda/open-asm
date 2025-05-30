<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Hash;

#[OA\Tag(name: 'Authentication', description: 'JWT Authentication endpoints')]
class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Login user and get JWT token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', description: 'User email'),
                    new OA\Property(property: 'password', type: 'string', description: 'User password'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'access_token', type: 'string', description: 'JWT access token'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
                        new OA\Property(property: 'expires_in', type: 'integer', description: 'Token expiration time in seconds'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            description: 'User roles'
                        ),
                        new OA\Property(
                            property: 'permissions',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            description: 'User permissions'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials',
                'error' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $user = JWTAuth::user();
        $roles = $user->getRoleNames()->toArray();
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/refresh',
        summary: 'Refresh JWT token',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token refreshed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'access_token', type: 'string', description: 'New JWT access token'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
                        new OA\Property(property: 'expires_in', type: 'integer', description: 'Token expiration time in seconds'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            description: 'User roles'
                        ),
                        new OA\Property(
                            property: 'permissions',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            description: 'User permissions'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Token refresh failed',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function refresh(): JsonResponse
    {
        try {
            // Get the token from the request
            $token = JWTAuth::getToken();
            if (! $token) {
                return response()->json([
                    'message' => 'Token refresh failed',
                    'error' => 'Token not found in request',
                ], 401);
            }

            // Refresh the token
            $newToken = JWTAuth::refresh($token);
            $user = JWTAuth::setToken($newToken)->toUser();
            $roles = $user->getRoleNames()->toArray();
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();

            return response()->json([
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'user' => $user,
                'roles' => $roles,
                'permissions' => $permissions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token refresh failed',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    #[OA\Get(
        path: '/api/v1/auth/me',
        summary: 'Get authenticated user information',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User information retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            description: 'User roles'
                        ),
                        new OA\Property(
                            property: 'permissions',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            description: 'User permissions'
                        ),
                        new OA\Property(property: 'organization', type: 'object', description: "User's organization"),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function me(): JsonResponse
    {
        $user = auth()->user();
        $roles = $user->getRoleNames()->toArray();
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        return response()->json([
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions,
            'organization' => $user->organization,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'Logout user and invalidate token',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successfully logged out',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Successfully logged out'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    #[OA\Put(
        path: '/api/v1/auth/password',
        summary: 'Change user password',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', description: 'Current password'),
                    new OA\Property(property: 'password', type: 'string', description: 'New password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', description: 'New password confirmation'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Password updated successfully.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        // Check if the current password is correct
        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'current_password' => ['The current password is incorrect.'],
                ],
            ], 422);
        }

        // Update the password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }
}
