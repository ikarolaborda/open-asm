<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'User Management', description: 'User and role management operations')]
class UserController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService
    ) {}

    #[OA\Get(
        path: '/api/v1/users',
        summary: 'List users',
        security: [['bearerAuth' => []]],
        tags: ['User Management'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'role', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'organization_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Users retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/User')),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $authUser = auth()->user();
        $query = User::with(['organization', 'roles', 'permissions']);

        if (! $authUser->isSuperAdmin()) {
            $query->where('organization_id', $this->tenantService->getCurrentOrganizationId());
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->string('role')));
        }

        if ($request->filled('organization_id') && $authUser->isSuperAdmin()) {
            $query->where('organization_id', $request->string('organization_id'));
        }

        $paginator = $query->paginate($request->integer('per_page', 15));

        return UserResource::collection($paginator)
            ->additional([
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                    'from'         => $paginator->firstItem(),
                    'to'           => $paginator->lastItem(),
                ],
            ]);
    }

    #[OA\Post(
        path: '/api/v1/users',
        summary: 'Create a new user',
        tags: ['User Management'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'first_name', type: 'string'),
                    new OA\Property(property: 'last_name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8),
                    new OA\Property(property: 'phone', type: 'string'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'department', type: 'string'),
                    new OA\Property(property: 'role', type: 'string', enum: ['super-admin', 'admin', 'user']),
                    new OA\Property(property: 'organization_id', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/User')
            ),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $authUser = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'role' => ['required', Rule::in(['super-admin', 'admin', 'user'])],
            'organization_id' => [
                'nullable',
                'uuid',
                'exists:organizations,id',
                // Super admins can assign to any org, others only to their own
                $authUser->isSuperAdmin() ? '' : Rule::in([$this->tenantService->getCurrentOrganizationId()]),
            ],
        ]);

        // Set organization_id if not provided and user is not super admin
        if (! $validated['organization_id'] && ! $authUser->isSuperAdmin()) {
            $validated['organization_id'] = $this->tenantService->getCurrentOrganizationId();
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = true;

        $user = User::create($validated);

        // Assign role
        $user->assignRole($validated['role']);

        $user->load(['organization', 'roles', 'permissions']);

        return response()->json($user, 201);
    }

    #[OA\Get(
        path: '/api/v1/users/{id}',
        summary: 'Get user details',
        tags: ['User Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User details retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/User')
            ),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $user = User::with(['organization', 'roles', 'permissions'])->findOrFail($id);

        // Check if user can access this user's details
        if (! $this->tenantService->belongsToCurrentOrganization($user)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    #[OA\Put(
        path: '/api/v1/users/{id}',
        summary: 'Update user',
        tags: ['User Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'first_name', type: 'string'),
                    new OA\Property(property: 'last_name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8),
                    new OA\Property(property: 'phone', type: 'string'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'department', type: 'string'),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                    new OA\Property(property: 'role', type: 'string', enum: ['super-admin', 'admin', 'user']),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/User')
            ),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Check if user can access this user
        if (! $this->tenantService->belongsToCurrentOrganization($user)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'phone' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
            'role' => ['sometimes', Rule::in(['super-admin', 'admin', 'user'])],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        // Update role if provided
        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        $user->load(['organization', 'roles', 'permissions']);

        return response()->json($user);
    }

    #[OA\Delete(
        path: '/api/v1/users/{id}',
        summary: 'Delete user',
        tags: ['User Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'User deleted successfully'),
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Check if user can access this user
        if (! $this->tenantService->belongsToCurrentOrganization($user)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Prevent users from deleting themselves
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete your own account'], 400);
        }

        $user->delete();

        return response()->json(null, 204);
    }

    #[OA\Get(
        path: '/api/v1/users/roles',
        summary: 'List all available roles',
        tags: ['User Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Roles retrieved successfully',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string')),
                        ]
                    )
                )
            ),
        ]
    )]
    public function roles(): JsonResponse
    {
        $roles = Role::with('permissions')->get();

        return response()->json($roles);
    }

    #[OA\Get(
        path: '/api/v1/users/permissions',
        summary: 'List all available permissions',
        tags: ['User Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Permissions retrieved successfully',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string'),
                        ]
                    )
                )
            ),
        ]
    )]
    public function permissions(): JsonResponse
    {
        $permissions = Permission::all();

        return response()->json($permissions);
    }
}
